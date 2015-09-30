<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Controller Translations
 */


class Controller_Panel_Translations extends Auth_Controller {

    public function __construct($request, $response)
    {
        parent::__construct($request, $response);
        Breadcrumbs::add(Breadcrumb::factory()->set_title(__('Translations'))->set_url(Route::url('oc-panel',array('controller'  => 'translations'))));
        
    }

    public function action_index()
    {

        // validation active 
        Breadcrumbs::add(Breadcrumb::factory()->set_title(__('List')));  
        $this->template->title = __('Translations');

        //scan project files and generate .po
        $parse = $this->request->query('parse');
        if($parse)
        {
            //scan script
            require_once Kohana::find_file('vendor', 'POTCreator/POTCreator','php');

            $obj = new POTCreator;
            $obj->set_root(DOCROOT);
            $obj->set_exts('php');
            $obj->set_regular('/_[_|e]\([\"|\']([^\"|\']+)[\"|\']\)/i');
            $obj->set_base_path('..');
            $obj->set_read_subdir(true);
            
            $obj->write_pot(i18n::get_language_path());
            Alert::set(Alert::SUCCESS, 'File regenerated');
        }

        //change default site language
        if($this->request->param('id'))
        {
         //save language
            $locale = new Model_Config();

            $locale->where('group_name','=','i18n')
                    ->where('config_key','=','locale')
                    ->limit(1)->find();

            if (!$locale->loaded())
            {
                $locale->group_name = 'i18n';
                $locale->config_key = 'locale';
            }

            $locale->config_value = $this->request->param('id');
            try {
                $locale->save();
                Alert::set(Alert::SUCCESS,__('Translations regenarated'));
            } catch (Exception $e) {
                throw HTTP_Exception::factory(500,$e->getMessage());
            }
            HTTP::redirect(Route::url('oc-panel',array('controller'  => 'translations'))); 
        }
        
        //create language
        if(Core::post('locale'))
        {
            $language   = $this->request->post('locale');
            $folder     = DOCROOT.'languages/'.$language.'/LC_MESSAGES/';
            
            // if folder does not exist, try to make it
            if ( !file_exists($folder) AND ! @mkdir($folder, 0775, true)) { // mkdir not successful ?
                Alert::set(Alert::ERROR, __('Language folder cannot be created with mkdir. Please correct to be able to create new translation.'));
                HTTP::redirect(Route::url('oc-panel',array('controller'  => 'translations')));  
            };
            
            // write an empty .po file for $language
            $out = 'msgid ""'.PHP_EOL;
            $out .= 'msgstr ""'.PHP_EOL;
            File::write($folder.'messages.po', $out);
            
            Alert::set(Alert::SUCCESS, $this->request->param('id').' '.__('Language saved'));
        }

        $this->template->content = View::factory('oc-panel/pages/translations/index',array('languages' => i18n::get_languages(),
                                                                                            'current_language' => core::config('i18n.locale')
                                                                                            ));

    }

    public function action_edit()
    {
        Breadcrumbs::add(Breadcrumb::factory()->set_title(__('Edit Translation')));  
        $this->template->title = __('Edit Translation');     
        $this->template->bind('content', $content);
        $content = View::factory('oc-panel/pages/translations/edit');
        $this->template->scripts['footer'][] = 'js/oc-panel/translations.js';

        $language   = $this->language_fix($this->request->param('id'));

        //get the translated ad not translated.
        list($translation_array,$untranslated_array) = $this->get_translation($language);
        
        //watch out at any standard php installation there's a limit of 1000 posts....edit php.ini max_input_vars = 10000 to amend it.
        if($this->request->post() AND ($data_translated = $this->request->post('translations')) )
        {
            if ($this->save_translation($language,$translation_array,$data_translated))
                Alert::set(Alert::SUCCESS, $language.' '.__('Language saved'));
            else
                Alert::set(Alert::ALERT, $language);

            $this->redirect(URL::current());
        }

        //add filters to search
        $translation_array_filtered = $translation_array;

        //only display not translated
        if (core::get('translated')==1)
        {
            $translation_array_filtered_aux = array();
            foreach ($untranslated_array as $key=>$value ) 
            {
                $translation_array_filtered_aux[] =  $translation_array_filtered[$value];
            }

            $translation_array_filtered = $translation_array_filtered_aux;
        }

        //how many translated items we have?
        $total_items = count($translation_array_filtered);

        //get elements for current page
        $pagination = Pagination::factory(array(
                    'view'           => 'oc-panel/crud/pagination',
                    'total_items'    => $total_items,
                    'items_per_page' => 20,
        ))->route_params(array(
                    'controller' => $this->request->controller(),
                    'action'     => $this->request->action(),
                    'id'         => $language,
        ));

        $trans_array_paginated = array();
        $from = $pagination->offset;
        $to   = $from + $pagination->items_per_page;

        for ($key=$from; $key <$to ; $key++) 
        { 
            if (isset($translation_array_filtered[$key]))
                $trans_array_paginated[$key] = $translation_array_filtered[$key];
        }

        $content->edit_language     = $language;
        $content->translation_array = $trans_array_paginated;
        $content->cont_untranslated = count($untranslated_array);
        $content->total_items       = count($translation_array);
        $content->pagination        = $pagination->render();

    }


    public function action_replace()
    {   
        $search     = Core::request('search');
        $replace    = Core::request('replace');
        $where      = Core::request('where','original');
    
        $language   = $this->language_fix($this->request->param('id'));

        //read original mo file to get the full array
        //read translated mo
        //get the translated ad not translated.
        //merge original with translated
        list($translation_array,$untranslated_array) = $this->get_translation($language);

        //array of new translations
        $data_translated = array();

        //for each item search
        foreach ($translation_array as $key => $values) 
        {
            //replace if theres a match
            list($id,$original,$translated) = array_values($values);

            switch ($where) {
                case 'translation':
                    //found in the translated
                    if (strpos($translated,$search)!==FALSE)
                    {
                        //add it to the new translations
                        $data_translated[$id] = str_replace($search,$replace,$translated);
                    }
                    break;
                
                case 'original':
                     //found in the original
                    if (strpos($original,$search)!==FALSE)
                    {
                        //add it to the new translations
                        $data_translated[$id] = str_replace($search,$replace,$original);
                    }
                    break;
            }            
        }

        if ($this->save_translation($language,$translation_array,$data_translated))
            Alert::set(Alert::SUCCESS, $language.' '.__('Language saved'));
        else
            Alert::set(Alert::ALERT, $language);

        $this->redirect(Route::url('oc-panel',array('controller'  => 'translations','action'=>'edit','id'=>$language)));
              
    }

    /**
     * gets the translation as array form a language
     * @param  string $language 
     * @return array           
     */
    public function get_translation($language)
    {
        $mo_translation = i18n::get_language_path($language);

        if(!file_exists($mo_translation))
        {
            Alert::set(Alert::ERROR, $language);
            $this->redirect(Route::url('oc-panel',array('controller'  => 'translations')));
        }

        $base_translation = i18n::get_language_path();

        //pear gettext scripts
        require_once Kohana::find_file('vendor', 'GT/Gettext','php');
        require_once Kohana::find_file('vendor', 'GT/Gettext/PO','php');
        require_once Kohana::find_file('vendor', 'GT/Gettext/MO','php');

        //load the .po files
        //original en translation
        $pocreator_en = new File_Gettext_PO();
        $pocreator_en->load($base_translation);
        //the translation file
        $pocreator_translated = new File_Gettext_PO();
        $pocreator_translated->load($mo_translation);

        //get an array with all the strings
        $en_array_order = $pocreator_en->strings;

        //sort alphabetical using locale
        ksort($en_array_order,SORT_LOCALE_STRING);
        
        //array with translated language may contain missing from EN
        $origin_translation = $pocreator_translated->strings;

        //lets get the array with translated values and sorted, will include everything even if was not previously saved
        $translation_array  = array();
        $untranslated_array = array();//keep track of words not translated stores ID
        
        $i = 0;
        foreach ($en_array_order as $origin => $value) 
        {
            //do we have the translation?
            if (isset($origin_translation[$origin]) AND !empty($origin_translation[$origin])>0)
            {
                $translated = $origin_translation[$origin];
            }
            else
            {
                $untranslated_array[] = $i;
                $translated = '';
            }

            $translation_array[] = array( 'id' => $i,
                                          'original' => $origin,
                                          'translated' => $translated);

            $i++;
        }

        return array($translation_array,$untranslated_array);
    }


    /**
     * saves a translation
     * @param  string $language          
     * @param  array $translation_array 
     * @param  array $data_translated   
     * @return bool                    
     */
    public function save_translation($language,$translation_array, $data_translated)
    {
        //.po to .mo script
        require_once Kohana::find_file('vendor', 'php-mo/php-mo','php');

        //we save always in the custom file
        $mo_translation = i18n::get_language_custom_path($language);

        //changing the translation_array with the posted values
        foreach($data_translated as $key => $value)
        {
            if (isset($translation_array[$key]['translated']))
                $translation_array[$key]['translated'] = $value;
        }

        //let's generate a proper .po file for the mo converter
        $out = '';

        foreach($translation_array as $key => $values)
        {
            list($id,$original,$translated) = array_values($values);
            if ($translated!='')
            {
                //only adding translated items
                $out .= '#: String '.$key.PHP_EOL;
                $out .= 'msgid "'.$original.'"'.PHP_EOL;
                $out .= 'msgstr "'.$translated.'"'.PHP_EOL;
                $out .= PHP_EOL;
            }
        }

        //write the generated .po to file
        if (File::write($mo_translation,$out)===FALSE)
            return FALSE;

        //generate the .mo from the .po file
        phpmo_convert($mo_translation);

        //we regenerate the file again to be poedit friendly
        $out = 'msgid ""
msgstr ""
"Project-Id-Version: '.Core::VERSION.'\n"
"POT-Creation-Date: '.Date::unix2mysql().'\n"
"PO-Revision-Date: '.Date::unix2mysql().'\n"
"Last-Translator: '.$this->user->name.' <'.$this->user->email.'>\n"
"Language-Team: en\n"
"Language: '.strtolower(substr($language,0,2)).'\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset='.i18n::$charset.'\n"
"Content-Transfer-Encoding: 8bit\n"
"X-Generator: Open Classifieds '.Core::VERSION.'\n"'.PHP_EOL.PHP_EOL;

        foreach($translation_array as $key => $values)
        {
            list($id,$original,$translated) = array_values($values);
            //only adding translated items
            $out .= '#: String '.$key.PHP_EOL;
            $out .= 'msgid "'.$original.'"'.PHP_EOL;
            $out .= 'msgstr "'.$translated.'"'.PHP_EOL;
            $out .= PHP_EOL;
        }

        //write the generated .po to file
        file_put_contents($mo_translation, $out, LOCK_EX);

        return TRUE;
    }

    /**
     * be sure is correct capital letters
     * @param  string $language 
     * @return string           
     */
    public function language_fix($language)
    {
        if (strlen($language)==5)
        {
            return  substr($language, 0,3).strtoupper(substr($language, 3,5));
        }
    }

}//end of controller