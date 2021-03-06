<?php
/**
 * Class and Function List:
 * Function list:
 * - __construct()
 * - index()
 * - create()
 * - paste()
 * - random()
 * - recent()
 * - trending()
 * - langs()
 * Classes list:
 * - Api extends Main
 */
include_once ('application/controllers/Main.php');

class Api extends Main
{
	
	function __construct() 
	{
		parent::__construct();
		
		if (config_item('disable_api')) 
		{
			die("The API has been disabled\n");
		}

        // if ldap is configured and no api token is configured, fail the request
        if ((config_item('require_auth') == true) && (config_item('apikey') == ''))
        {
             die("API key not configured");
        }

	}
	
	function index() 
	{
		$languages = $this->languages->get_languages();
		$languages = array_keys($languages);
		$languages = implode(', ', $languages);
		$data['languages'] = $languages;
		$this->load->view('api_help', $data);
	}
	
	function create() 
	{
		
		if (config_item('apikey') != $this->input->get('apikey') && config_item('soft_api') == false) 
		{
			die("Invalid API key\n");
		}
		$this->load->model('pastes');
		$this->load->library('form_validation'); //needed by parent class

		
		if (!$this->input->post('text')) 
		{
			$data['msg'] = json_encode(array('error' => 'Missing paste text'));
			$this->load->view('view/api', $data);
		}
		else
		{
			
			if (!$this->input->post('lang')) 
			{
				$_POST['lang'] = 'text';
			}
			$_POST['code'] = $this->input->post('text');
			
			if ($this->config->item('private_only')) 
			{
				$_POST['private'] = 1;
			}

			//validations
			
			if (!$this->_valid_ip()) 
			{
                $data['msg'] = json_encode(array('error' => 'You are not allowed to paste'));
                $this->load->view('view/api', $data);
			}
			
			if (config_item('soft_api') == true && (config_item('apikey') == $this->input->get('apikey'))) 
			{

				//pass
				
			}
			else
			{
				
				if (!$this->_blockwords_check()) 
				{
                    $data['msg'] = json_encode(array('error' => 'Your paste contains blocked words'));
                    $this->load->view('view/api', $data);
				}
			}

			if (!$this->input->post('expire')) 
			{
				$_POST['expire'] = config_item('default_expiration');
			}

			//create paste
			$paste_url = $this->pastes->createPaste();
			$data['msg'] = json_encode(array('url' => base_url() . $paste_url));
			$this->load->view('view/api', $data);
		}
	}
	
	function paste() 
	{
		
		if (config_item('apikey') != $this->input->get('apikey')) 
		{
			die("Invalid API key\n");
		}
		
		$this->load->model('pastes');
		$check = $this->pastes->checkPaste(3);
		
		if ($check) 
		{
			$data = $this->pastes->getPaste(3);
		}
		else
		{
			$data = array(
				'message' => 'Not found',
			);
		}
        header('Content-Type: application/json');
		echo json_encode($data);
	}
	
	function random() 
	{
		
		if (config_item('apikey') != $this->input->get('apikey')) 
		{
			die("Invalid API key\n");
		}
		
		if (config_item('private_only')) 
		{
			show_404();
		}
		$this->load->model('pastes');
		$data = $this->pastes->random_paste();
        header('Content-Type: application/json');
		echo json_encode($data);
	}
	
	function recent() 
	{
		
		if (config_item('apikey') != $this->input->get('apikey')) 
		{
			die("Invalid API key\n");
		}
		
		if (config_item('private_only')) 
		{
			show_404();
		}
		$this->load->model('pastes');
		$pastes = $this->pastes->getLists('api/recent');
		$pastes = $pastes['pastes'];
		$data = array();
		foreach ($pastes as $paste) 
		{
			$data[] = array(
				'pid' => $paste['pid'],
				'title' => $paste['title'],
				'name' => $paste['name'],
				'created' => $paste['created'],
				'lang' => $paste['lang'],
			);
		}
        header('Content-Type: application/json');
		echo json_encode($data);
	}
	
	function trending() 
	{
		
		if (config_item('apikey') != $this->input->get('apikey')) 
		{
			die("Invalid API key\n");
		}
		
		if (config_item('private_only')) 
		{
			show_404();
		}
		$this->load->model('pastes');
		$pastes = $this->pastes->getTrends('api/trending', 2);
		$pastes = $pastes['pastes'];
		$data = array();
		foreach ($pastes as $paste) 
		{
			$data[] = array(
				'pid' => $paste['pid'],
				'title' => $paste['title'],
				'name' => $paste['name'],
				'created' => $paste['created'],
				'lang' => $paste['lang'],
				'hits' => $paste['hits'],
			);
		}
        header('Content-Type: application/json');
		echo json_encode($data);
	}
	
	function langs() 
	{
		if (config_item('apikey') != $this->input->get('apikey')) 
		{
			die("Invalid API key\n");
		}
		
		$languages = $this->languages->get_languages();
        header('Content-Type: application/json');
		echo json_encode($languages);
	}
}
