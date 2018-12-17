<?php
class givePaypingSettingsPage
{   
    private $options;

    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    public function add_plugin_page()
    {
        add_menu_page( 
            'تنظیمات پی‌پینگ برای GIVE', 
            'تنظیمات پی‌پینگ برای GIVE', 
            'edit_others_pages', 
            'give-payping-setting-admin', 
            array( $this, 'create_options_page' )
        );
    }
    
    public function page_init()
    {
        register_setting(
            'givePaypingOptionsGroup', 
            'givePaypingOptions',
            array( $this, 'sanitize' ) 
        );
        
        add_settings_section(
            'give-payping-settings', 
            '', 
            array( $this, 'print_section_info' ), 
            'give-payping-setting-admin' 
        );  
        add_settings_field(
            'givePayping_PaypingG_Token',
            'توکن دریافت شده از پی‌پینگ',
            array( $this, 'givePayping_PaypingG_Token_callback' ), 
            'give-payping-setting-admin', 
            'give-payping-settings'      
        );
    }
    
    public function givePayping_PaypingG_Token_callback()
    {
        printf(
            '<input type="text" id="givePayping_PaypingG_Token" name="givePaypingOptions[givePayping_PaypingG_Token]" value="%s" />',
            isset( $this->options['givePayping_PaypingG_Token'] ) ? esc_attr( $this->options['givePayping_PaypingG_Token']) : ''
        );
    }
    public function sanitize( $input )
    {
        $new_input = array();
        
        $new_input['givePayping_PaypingG_Token'] = isset($input['givePayping_PaypingG_Token']) 
                ? $input['givePayping_PaypingG_Token'] : '';


        return $new_input;
    }

    public function print_section_info()
    {
    }
    
    public function create_options_page()
    {
        $this->options = get_option( 'givePaypingOptions' );
        
        echo '<div class="wrap">';
        screen_icon();
        echo '<h2>تنظیمات</h2>';
        echo '<form method="post" action="options.php">';
        settings_fields( 'givePaypingOptionsGroup' );
        do_settings_sections( 'give-payping-setting-admin' );
        submit_button();
        echo '</form>';
        echo '</div>';
    }
}

if( is_admin() )
    $give_payping_page = new givePaypingSettingsPage();
