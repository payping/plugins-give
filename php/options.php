<?php
const OPTION_KEY = 'givePaypingOptions';

function setGivePaypingOptions()
{
    $options = get_option(OPTION_KEY);
    
    $options['homeUrl'] = get_site_url() . '/';
    
    update_option(OPTION_KEY, $options);
}
