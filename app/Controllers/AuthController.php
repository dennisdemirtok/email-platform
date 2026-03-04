<?php

namespace App\Controllers;

use App\Models\UsersModel;

class AuthController extends BaseController
{
    public function login()
    {
        $session = session();

        if ($session->has('isLoggedIn')) {
            return redirect()->to('/');
        }

        helper(['form']);

        echo view('Auth/login');
    }

    public function doLogin()
    {
        $session = session();

        // Rate limiting: max 5 attempts per 15 minutes
        $attempts = $session->get('login_attempts') ?? 0;
        $lastAttempt = $session->get('login_last_attempt') ?? 0;

        if ($attempts >= 5 && (time() - $lastAttempt) < 900) {
            $remaining = ceil((900 - (time() - $lastAttempt)) / 60);
            $session->setFlashdata('error', "Too many login attempts. Please try again in {$remaining} minutes.");
            return redirect()->to('/login');
        }

        if ((time() - $lastAttempt) >= 900) {
            $session->set('login_attempts', 0);
            $attempts = 0;
        }

        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        if ($this->validate($rules)) {
            $username = $this->request->getPost('username');
            $password = $this->request->getPost('password');

            $usersModel = new UsersModel();
            $user = $usersModel->findByUsername($username);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Successful login
                $session->remove('login_attempts');
                $session->remove('login_last_attempt');

                $session->set('isLoggedIn', true);
                $session->set('username', $username);
                $session->set('user_id', $user['id']);
                $session->set('user_role', $user['role']);

                // For regular users, store their allowed domain IDs
                if ($user['role'] !== 'super') {
                    $allowedDomainIds = $usersModel->getUserDomainIds($user['id']);
                    $session->set('allowed_domain_ids', $allowedDomainIds);
                }

                log_message('info', 'User logged in successfully: ' . $username . ' (role: ' . $user['role'] . ')');
                return redirect()->to('/campaigns');
            } else {
                $session->set('login_attempts', $attempts + 1);
                $session->set('login_last_attempt', time());
                log_message('warning', 'Failed login attempt for username: ' . $username);
                $session->setFlashdata('error', 'Invalid login credentials');
            }
        } else {
            $session->setFlashdata('error', 'Please fill in all required fields');
        }

        return redirect()->to('/login')->withInput();
    }

    public function logout()
    {
        $session = session();
        $session->remove('isLoggedIn');
        $session->remove('username');
        $session->remove('user_id');
        $session->remove('user_role');
        $session->remove('allowed_domain_ids');
        // Clear domain cache on logout
        $session->remove('cached_domains');
        $session->remove('cached_domains_at');
        return redirect()->to('/login');
    }
}
