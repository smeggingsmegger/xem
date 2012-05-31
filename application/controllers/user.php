<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class User extends SuperController {
    var $humanReadableErrors = array('insuficentData'=>'You missed to provide importent information',
	                                 'emailInUse'=>'This email is assotiated with another account',
	                                 'nickInUse'=>'This username is allready in use',
	                                 'lvlZero'=>'Account not activated or banned!',
	                                 'usernameOrPasswordWrong'=>'Wrong combination of username and password');

	function __construct(){
		parent::__construct();
	    $this->load->library('recaptcha');
	    $this->load->library('form_validation');
	    $this->load->helper('form');
	    $this->lang->load('recaptcha');


	    //email stuff
        $this->load->helper('email'); // only needed to validate the emai adress
	    $this->load->library('email');
        $this->email->from('info@thexem.de', 'XEM');
	}

	public function index(){
		if(!$this->session->userdata('logged_in')) {
			redirect('user/login');
		}
		$this->out['title'] = 'User Center';
		$this->_loadView(); // nothing means load the index for the current controller
	}

	public function login(){
		$this->out['title'] = 'Login and Registration';

		if(isset($_POST['user'])&&isset($_POST['pw'])){
			if($this->simpleloginsecure->login($_POST['user'],$_POST['pw'])){
				redirect($this->out['uri2']);
			}else
				$this->out['login_unsuccessfull'] = true;
		}

		if(!$this->session->userdata('logged_in')){
		    if (isset($this->out['login_unsuccessfull'])) {
		        $this->out['reson'] = $this->humanReadableErrors[$this->simpleloginsecure->last_error];
		    }


			$this->_loadView('login');
		}else{
			redirect($this->out['uri2']);
		}
	}
    function register() {
		$this->out['title'] = 'Registration';
		// recapcha stuff
		$this->form_validation->set_rules('user', 'user', 'required');
		$this->form_validation->set_rules('pw', 'pw', 'required|matches[pw_check]');
		$this->form_validation->set_rules('pw_check', 'pw_check', 'required');
		$this->form_validation->set_rules('email', 'email', 'required');
		$this->form_validation->set_rules('recaptcha_response_field', 'lang:recaptcha_field_name', 'required|callback_check_captcha');
        
        
        $registration_open = false;

	    if ($this->form_validation->run() && $registration_open){
    	    if(valid_email($_POST['email'])){
    	        $userdata = $this->simpleloginsecure->create($_POST['user'], $_POST['email'], $_POST['pw']);
    	        if($userdata){
    	            // user activation code
    	            $this->email->to($_POST['email']);
                    $this->email->subject('Registration at XEM');
                    $emailBody = $this->load->view('email/registration_code', $userdata, true);
                    $this->email->message($emailBody);
                    $this->email->send();
                    //echo $this->email->print_debugger();
            		$this->_loadView('registerConfirm');
            		return true;
    	        }else{
    				$this->out['register_unsuccessfull'] = true;
    		        $this->out['reson'] = $this->humanReadableErrors[$this->simpleloginsecure->last_error];
    	        }
    	    }else{
                $this->out['register_unsuccessfull'] = true;
		        $this->out['reson'] = 'That is not a valid email';
    	    }
	    }elseif(isset($_POST['user']) && isset($_POST['email']) && isset($_POST['pw'])){
            $this->out['register_unsuccessfull'] = true;
	        $this->out['reson'] = 'Wrong Capcha';
	        //echo validation_errors();
	    }
	    if(!$registration_open){
            $this->out['register_unsuccessfull'] = true;
            $this->out['reson'] = 'Registration closed!!';
	    }
	    $this->out['recaptcha'] = $this->recaptcha->get_html();
		$this->_loadView('register');
    }

    function activate() {
        $userdata = $this->simpleloginsecure->activate($this->uri->segment(3));
        if($userdata){
            // info mail
            $this->email->to('info@thexem.de');
            $this->email->subject('New Account | '.$userdata['user_nick']);
            $emailBody = $this->load->view('email/registration_info', $userdata, true);
            $this->email->message($emailBody);
            $this->email->send();
			redirect('user/login');
        }else
    		$this->_loadView('register');
    }

	function logout(){
		if(!$this->session->userdata('logged_in')){
			$this->_loadView('login');
		}else{
			$this->simpleloginsecure->logout();
			redirect($this->out['uri2']);
		}
	}

	function changePW(){
		if(!$this->session->userdata('logged_in')){
			$this->_loadView('login');
		}else{
			if($_POST['new_pw']==$_POST['new_pw_check']){
				if($this->simpleloginsecure->changePassword($this->session->userdata('user_id'), $_POST['old_pw'], $_POST['new_pw'])){
					$this->simpleloginsecure->logout();
					$this->_loadView('login');
				}else{
					$this->_loadView();
				}
			}else{
				$this->_loadView();
			}
		}
	}
}
?>