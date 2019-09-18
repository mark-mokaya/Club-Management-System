<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @name LoginCtrl.php
 * @Mokoro
 */

//controller to login users
class LoginCtrl extends CI_Controller
{

    //constructor to initialize variables and load tools
    function __construct()

    {
        parent::__construct();
        $this->output->set_header('Last-Modified:'.gmdate('D, d M Y H:i:s').'GMT');
        $this->output->set_header('Cache-Control: no-store, no-cache, must-revalidate');
        $this->output->set_header('Cache-Control: post-check=0, pre-check=0',false);
        $this->output->set_header('Pragma: no-cache');

        $this->load->model("LoginModel", "login");
        $this->load->model("MainModel", "mainmodel");

    }

public function login()
    {
        $this->cas->force_auth();//calling CAS for authentication
        $user = $this->cas->user();//calling the array with username from CAS and assignin it to the variable $user - @smokoro
        //lower case
        $username = strtolower($user->userlogin);
        //$username = $this->input->post('username');

              $result = $this->login->validate_ldap_official($username);//clubofficial
              $result3 = $this->login->validate_ldap_stdcouncil($username);//clubofficial//student council (student)
              $result2 = $this->login->validate_ldap_admin($username);//admin



            if($result&&!$result3)
                {
                    //take the returned data and create a session for it.
                    foreach ($result as $row)
                            {
                                $fullName=$row->firstName." ".$row->lastName;
                                $studentID=$row->studentID;

                                $sessdata=array();
                                $sessdata = array('officialName' =>$fullName,'suID'=>$studentID,'clubhead_login'=>TRUE);
                                $this->session->set_userdata($sessdata);


                            }
                            redirect('ClubController/rolespage');
                }else if($result2)
                {
                    //take the returned data and create a session for it (adminName and adminID).
                    foreach ($result2 as $row)
                            {
                                $fullName=$row->firstName."&nbsp;".$row->lastName;
                                $userID=$row->staffID;
                                $roleID=$row->roleID;
                                $sessdata=array();

                                if($roleID==1||$roleID==2)
                                    {
                                       $sessdata = array('adminName' =>$fullName,'adminID'=>$userID,'admin_login'=>TRUE);
                                        $this->session->set_userdata($sessdata);

                                        $redirectLink="Home/admin";
                                   }

                            }
                            redirect($redirectLink);
                }else if($result3&&!$result)
                    {
                        //take the returned data and create a session for it (adminName and adminID) for clubs representative-student council.
                        foreach ($result3 as $row)
                                {
                                    $fullName=$row->firstName."&nbsp;".$row->lastName;
                                    $userID=$row->staffID;
                                    $roleID=$row->roleID;
                                    $sessdata=array();

                                           $sessdata = array('crepName' =>$fullName,'crepID'=>$userID,'crep_login'=>TRUE);
                                            $this->session->set_userdata($sessdata);

                                }
                                redirect('Home/crep');
                    }else if($result3 && $result)
                    {
                        //if the student council member has an active role in any club then prevent access. Not allowed to be clubs rep and club official.

                        $_SESSION['msg'] = array('error' => "Login failed",'success'=>'');

                            redirect('Home/crep');

                    }else
                        {
                          $_SESSION['msg'] = array('error' => "Wrong credentials. Please try again",'success'=>'');
                                // $this->cas->logout();
                                redirect(base_url('Home'));

                        }


       }

public function usertype()
    {

        $clubID= $this->input->post('clubid');
        $clubsess=$this->session->userdata('clubEmail');

        if($clubID=="")
                {
                    // $this->load->view('clubheads/roleselect');
                    redirect('ClubController/rolespage');
                }else if($clubID !=="") {
                            $this->session->set_userdata('clubEmail', $clubID);

                            $this->db->limit(1);
                            $this->db->select('*');
                            $this->db->from('clubs');
                            $this->db->where('clubID',$clubID);
                            $result=$this->db->get()->result();

                            $clubName="";
                            foreach ($result as $club)
                                    {
                                        $clubName=$club->clubName;
                                        $this->session->set_userdata('clubName', $clubName);

                                    }

                            redirect('ClubController/clubpage');

                            // $this->load->view('clubheads/clubmemberreg');
                        }

        if($clubsess!=="" && $clubID=="")
            {
                redirect('ClubController/rolespage');
            }

    }

public function logoutadmin()
    {
        $this->session->unset_userdata('fullName');
        $this->session->unset_userdata('admin_login');
        $this->session->unset_userdata('suID');

        $data = array('profile'=>$this->mainmodel->clubProfile());

        $this->cas->logout($url = ''); // Log out of CAS @lkasera
        $this->load->view('login',$data);
    }

public function logoutclub()
    {
        $this->session->unset_userdata('clubName');
        $this->session->unset_userdata('clubEmail');
        $this->session->unset_userdata('clubhead_login');
        $this->session->unset_userdata('officialName');
        // $data = array('profile'=>$this->mainmodel->clubProfile());
        // $this->load->view('login',$data);

        $this->cas->logout($url = ''); // Log out of CAS @lkasera
        redirect(base_url(('Home')));

    }
}


?>
