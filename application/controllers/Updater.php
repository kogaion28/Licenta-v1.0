<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/*
 *    @author     : Jude Bogdan Laurentiu
 *    date        : 2019
 *    MedicSoft
 */

class Updater extends CI_Controller
{
    
    
    function __construct()
    {
        parent::__construct();
        $this->load->database();
        $this->load->library('session');
        $this->load->model('crud_model');
        $this->load->model('email_model');
        $this->load->model('sms_model');
        $this->load->model('frontend_model');
        
        /*cache control*/
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $this->output->set_header('Pragma: no-cache');
        
    }
    
    /***funcția implicită, redirecționează la pagina de conectare dacă nu a fost înregistrat încă un administrator***/
    public function index()
    {
        if ($this->session->userdata('admin_login') != 1)
            redirect(site_url('login'), 'refresh');
        if ($this->session->userdata('admin_login') == 1)
            redirect(site_url('admin/dashboard'), 'refresh');
    }
    
    /***** PRODUSUL UPDATE *****/
    
    function update($task = '', $purchase_code = '')
    {
        
        if ($this->session->userdata('admin_login') != 1)
            redirect(site_url(), 'refresh');
        // Creați directorul de actualizare.
        $dir = 'update';
        if (!is_dir($dir))
            mkdir($dir, 0777, true);
        
        $zipped_file_name = $_FILES["file_name"]["name"];
        $path             = 'update/' . $zipped_file_name;
        
        move_uploaded_file($_FILES["file_name"]["tmp_name"], $path);
        
        //Dezarhivați fișierul de actualizare încărcat și eliminați fișierul zip.
        $zip = new ZipArchive;
        $res = $zip->open($path);
        if ($res === TRUE) {
            $zip->extractTo('update');
            $zip->close();
            unlink($path);
        }
        
        $unzipped_file_name = substr($zipped_file_name, 0, -4);
        $str                = file_get_contents('./update/' . $unzipped_file_name . '/update_config.json');
        $json               = json_decode($str, true);
        
        
        //Executați modificări în php
        require './update/' . $unzipped_file_name . '/update_script.php';
        
        // Create new directories.
        if (!empty($json['directory'])) {
            foreach ($json['directory'] as $directory) {
                if (!is_dir($directory['name']))
                    mkdir($directory['name'], 0777, true);
            }
        }
        
        // Creați / înlocuiți fișiere noi.
        if (!empty($json['files'])) {
            foreach ($json['files'] as $file)
                copy($file['root_directory'], $file['update_directory']);
        }
        
        $this->session->set_flashdata('message', get_phrase('actualizare_produs_cu_succes'));
        redirect(site_url('admin/system_settings'), 'refresh');
    }
    
}