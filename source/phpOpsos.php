<?
include('vendor/simple_html_dom.php');

//$opsos = new phpOpsos(array('phone' => '', 'password' => 'default', 'opsos' => 'megafon'));
//$opsos = new phpOpsos(array('phone' => '', 'password' => 'default', 'opsos' => 'mts'));
//$opsos = new phpOpsos(array('phone' => '', 'password' => '376841', 'opsos' => 'megafon', 'region' => 'tatarstan'));

$opsos->go();

Class phpOpsos {

    public $phone;
    public $password;
    public $opsos;
    public $settings;
    
    public $defaults = array(
        'megafon' => array(
            'encoding' => 'cp1251',
            'uri_auth' => 'TRAY_INFO/TRAY_INFO',
            'uri_info' => 'SCWWW/ACCOUNT_INFO',
            'regions' => array(
                'moscow' => 'https://www.serviceguide.megafonmoscow.ru/',
                'tatarstan' => 'https://serviceguide.megafonvolga.ru/',
            ),
            'default_region' => 'moscow',
        ),
        'mts' => array(
            'uri_auth' => 'SELFCAREPDA/Security.mvc/LogOn',
            'uri_info' => 'SELFCAREPDA/Account.mvc/Status',
            'regions' => array(
                'moscow' => 'https://ihelper.mts.ru/',
            ),
            'default_region' => 'moscow',
        ),

    );
    
    function __construct($settings)
    {
        if (isset($settings['phone']))
        {
            $this->phone = $settings['phone'];
        }

        if (isset($settings['password']))
        {
            $this->password = $settings['password'];
        }
        
        if (isset($settings['opsos']))
        {
            $this->settings = $this->defaults[$settings['opsos']];
            $this->opsos = $settings['opsos'];
        }
        
        if (isset($settings['region'])){
            $this->settings['url'] = $this->settings['regions'][$settings['region']];
        }
        else
        {
            $this->settings['url'] = $this->settings['regions'][$this->settings['default_region']];
        }
    }
    
    protected function query($url, $post='', $cookie = 'save')
    {
        $ckfile = $this->phone.'.txt';
    
        $ch = curl_init();  
        curl_setopt($ch, CURLOPT_URL,$url); // set url to post to  
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);  
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);// allow redirects  
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable  
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // times out after 4s  
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); // this line makes it work under https
        
        if ( ! empty($post))
        {
            curl_setopt($ch, CURLOPT_POST, 1); // set POST method  
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post); // add POST fields
        }
        
        if($cookie == 'save')
        {
            curl_setopt ($ch, CURLOPT_COOKIEJAR, $ckfile); 
        }
        elseif($cookie == 'get')
        {
            curl_setopt ($ch, CURLOPT_COOKIEFILE, $ckfile); 
        }
          
        $result = curl_exec($ch); // run the whole process  
        
        curl_close($ch);   
        
        if (isset($this->settings['encoding']))
        {
            $result = iconv($this->settings['encoding'], 'utf-8', $result);
        }
        
        return $result;
    }
    
    public function go()
    {
        $function = "go_".$this->opsos;
        
        return $this->$function();
    }
    
    protected function go_megafon()
    {
        $info = array();
    
        $url = $this->settings['url'].$this->settings['uri_auth'];
        
        $post = "LOGIN={$this->phone}&PASSWORD={$this->password}";

        $result = $this->query($url, $post);
        
        $xml = simplexml_load_string($result);
        
        $session_id = $xml->SESSION_ID;
        
        $url = $this->settings['url'].$this->settings['uri_info'];
        
        $post = "SESSION_ID={$session_id}";
        
        $result = $this->query($url, $post);

        $html = str_get_html($result);
        
        //$info['status'] = $html->find(".group-info", 2)->find(".grid-row", 0)->find("td", 2)->find("div", 0)->plaintext;
        //$info['tarif'] = $html->find(".group-info", 2)->find(".grid-row", 1)->find("td", 2)->find("name", 0)->plaintext;
        $info['balance'] = $html->find(".group-info", 0)->find(".grid-row", 1)->find("td", 2)->find("div", 0)->plaintext;
        $info['credit'] = $html->find(".group-info", 0)->find(".grid-row", 2)->find("td", 2)->find("div", 0)->plaintext;        
        /*
        foreach ($html->find(".group-info", 3)->find(".grid-row") as $tr)
        {
            $info['use'][] = array(
                'key' => $tr->find("td", 0)->find("div", 0)->plaintext,
                'value' => $tr->find("td", 2)->find("div", 0)->plaintext
            );
        }
        
        foreach ($html->find(".grid-cont", 0)->find(".grid-row-fill") as $tr)
        {
            if (isset($tr->find("td", 2)->find("div", 0)->plaintext))
            {
                $info['services'][] = array(
                    'key' => $tr->find("td", 0)->find("name", 0)->plaintext,
                    'value' => $tr->find("td", 2)->find("div", 0)->plaintext,
                    'all' => $tr->find("td", 1)->find("div", 0)->plaintext,
                    'expiry' => $tr->find("td", 3)->find("div", 0)->plaintext,
                );
            }
        }
        */
        echo '<pre>';
        var_dump($info);
    }
    
    protected function go_mts()
    {
        $info = array();
    
        $url = $this->settings['url'].$this->settings['uri_auth'];
        
        $post = "username={$this->phone}&password={$this->password}";

        $result = $this->query($url, $post);
        
        $url = $this->settings['url'].$this->settings['uri_info'];

        $result = $this->query($url, NULL, 'get');
        
        $html = str_get_html($result);
        
        var_dump($result);
    }
}