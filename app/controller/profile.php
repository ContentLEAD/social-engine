<?php


class profile extends controller{


    public function index(){
        
        
        $this->fez->load->view('header');
        $this->fez->load->view('profile/index');
        $this->fez->load->view('footer');
    
    }

}