<?php defined('SYSPATH') or die('No direct script access.');?>
<div class="btn-group">
    <?if(count(Auth::instance()->get_user()->contacts()) > 0):?>
        <div class="btn-group navbar-btn" role="group">
            <a class="btn dropdown-toggle btn-success" data-toggle="dropdown"
                href="#" id="contact-notification" data-url="<?=Route::url('oc-panel', array('controller'=>'profile', 'action'=>'notifications'))?>"> <i class="fa fa-bell"></i> <span class="badge"><?=count(Auth::instance()->get_user()->contacts())?></span>
            </a>
            <ul id="contact-notification-dd" class="dropdown-menu">
                <li class="dropdown-header"><?=__('Please check your email')?></li>
                <li class="divider"></li>
                <li class="dropdown-header"><?=__('You have been contacted for these ads')?></li>
                <?foreach (Auth::instance()->get_user()->contacts() as $ad ):?>
                    <li class="dropdown-header"><strong><?=$ad["title"]?></strong></li>
                <?endforeach?>
            </ul>
        </div>
    <?endif?>
    <a class="btn btn-success navbar-btn"
        href="<?=Route::url('oc-panel',array('controller'=>'home','action'=>'index'))?>">
        <i class="glyphicon-user glyphicon"></i> 
    </a>
    <div class="btn-group" role="group">
        <a class="btn dropdown-toggle btn-success navbar-btn"  data-toggle="dropdown"
            href="#"> <span class="caret"></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-right">
        
            <li><a href="<?=Route::url('oc-panel',array('controller'=>'home','action'=>'index'))?>">
                <i class="glyphicon glyphicon-cog"></i> <?=__('Panel')?></a></li>
        
            <li><a href="<?=Route::url('oc-panel',array('controller'=>'myads','action'=>'index'))?>"><i
                   class="glyphicon glyphicon-edit"></i> <?=__('My Advertisements')?></a></li>
        
            <li><a href="<?=Route::url('oc-panel',array('controller'=>'profile','action'=>'favorites'))?>"><i
                   class="glyphicon glyphicon-heart"></i> <?=__('My Favorites')?></a></li>
        
            <li><a href="<?=Route::url('oc-panel',array('controller'=>'profile','action'=>'orders'))?>"><i
                   class="glyphicon glyphicon-shopping-cart"></i> <?=__('My Payments')?></a></li>
        
            <li><a href="<?=Route::url('oc-panel',array('controller'=>'profile','action'=>'subscriptions'))?>"><i
                   class="glyphicon glyphicon-envelope"></i> <?=__('Subscriptions')?></a></li>

            <?if (core::config('general.messaging') == TRUE):?>
                <li>
                    <a href="<?=Route::url('oc-panel',array('controller'=>'messages','action'=>'index'))?>">
                        <i class="fa fa-inbox"></i> <?=__('Messages')?>
                    </a>
                </li>
            <?endif?>
        
            <li><a href="<?=Route::url('oc-panel',array('controller'=>'profile','action'=>'edit'))?>"><i
                   class="glyphicon glyphicon-lock"></i> <?=__('Edit profile')?></a></li>
        
            <li><a href="<?=Route::url('profile',array('seoname'=>Auth::instance()->get_user()->seoname))?>">
                 <i class="glyphicon glyphicon-eye-open"></i> <?=__('Public profile')?></a></li>
        
            <li class="divider"></li>
            <li><a
                href="<?=Route::url('oc-panel',array('directory'=>'user','controller'=>'auth','action'=>'logout'))?>">
                    <i class="glyphicon glyphicon-off"></i> <?=__('Logout')?>
            </a></li>
            <li>
                <a
                href="<?=Route::url('default')?>">
                    <i class="glyphicon glyphicon-home"></i> <?=__('Visit Site')?></a>
            </li>
        </ul>
    </div>
</div>
