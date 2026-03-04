<?php

namespace App\Controllers;

use App\Models\UsersModel;
use App\Models\DomainsModel;

class UsersController extends BaseController
{
    protected $usersModel;

    public function __construct()
    {
        $this->usersModel = new UsersModel();
        helper('domain');
    }

    /**
     * List all users.
     */
    public function index()
    {
        $users = $this->usersModel->getAll();

        echo view('Templates/header', ['currentPage' => 'users']);
        echo view('Users/index', ['users' => $users]);
        echo view('Templates/footer');
    }

    /**
     * Show create user form / handle create POST.
     */
    public function create()
    {
        if (strtolower($this->request->getMethod()) === 'post') {
            return $this->store();
        }

        // GET: show form
        $domainsModel = new DomainsModel();
        $domains = $domainsModel->getActiveDomains();

        echo view('Templates/header', ['currentPage' => 'users']);
        echo view('Users/create', ['domains' => $domains]);
        echo view('Templates/footer');
    }

    /**
     * Handle POST to create a new user.
     */
    private function store()
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[50]',
            'password' => 'required|min_length[6]',
            'role'     => 'required|in_list[super,user]',
        ];

        if (!$this->validate($rules)) {
            session()->setFlashdata('error', 'Please check your input. Username min 3 chars, password min 6 chars.');
            return redirect()->to('/users/create')->withInput();
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $role     = $this->request->getPost('role');

        // Check if username already exists
        $existing = $this->usersModel->findByUsername($username);
        if ($existing) {
            session()->setFlashdata('error', 'Username already exists.');
            return redirect()->to('/users/create')->withInput();
        }

        $userId = $this->usersModel->create([
            'username'      => $username,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
            'role'          => $role,
        ]);

        if (!$userId) {
            session()->setFlashdata('error', 'Failed to create user.');
            return redirect()->to('/users/create')->withInput();
        }

        // Assign domains if role is 'user'
        if ($role === 'user') {
            $domainIds = $this->request->getPost('domains') ?? [];
            if (!empty($domainIds)) {
                $this->usersModel->setUserDomains($userId, $domainIds);
            }
        }

        session()->setFlashdata('success', 'User created successfully.');
        return redirect()->to('/users');
    }

    /**
     * Show edit user form / handle edit POST.
     */
    public function edit($id = null)
    {
        if ($id === null) {
            return redirect()->to('/users');
        }

        $user = $this->usersModel->findById($id);
        if (!$user) {
            session()->setFlashdata('error', 'User not found.');
            return redirect()->to('/users');
        }

        if (strtolower($this->request->getMethod()) === 'post') {
            return $this->update($id, $user);
        }

        // GET: show form
        $domainsModel = new DomainsModel();
        $domains = $domainsModel->getActiveDomains();
        $userDomainIds = $this->usersModel->getUserDomainIds($id);

        echo view('Templates/header', ['currentPage' => 'users']);
        echo view('Users/edit', [
            'user'          => $user,
            'domains'       => $domains,
            'userDomainIds' => $userDomainIds,
        ]);
        echo view('Templates/footer');
    }

    /**
     * Handle POST to update a user.
     */
    private function update(string $id, array $user)
    {
        $rules = [
            'username' => 'required|min_length[3]|max_length[50]',
            'role'     => 'required|in_list[super,user]',
        ];

        if (!$this->validate($rules)) {
            session()->setFlashdata('error', 'Please check your input.');
            return redirect()->to('/users/edit/' . $id)->withInput();
        }

        $username = $this->request->getPost('username');
        $role     = $this->request->getPost('role');

        // Check if username changed and already exists
        if ($username !== $user['username']) {
            $existing = $this->usersModel->findByUsername($username);
            if ($existing) {
                session()->setFlashdata('error', 'Username already exists.');
                return redirect()->to('/users/edit/' . $id)->withInput();
            }
        }

        $updateData = [
            'username' => $username,
            'role'     => $role,
        ];

        // Only update password if provided
        $password = $this->request->getPost('password');
        if (!empty($password)) {
            if (strlen($password) < 6) {
                session()->setFlashdata('error', 'Password must be at least 6 characters.');
                return redirect()->to('/users/edit/' . $id)->withInput();
            }
            $updateData['password_hash'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $success = $this->usersModel->update($id, $updateData);
        if (!$success) {
            session()->setFlashdata('error', 'Failed to update user.');
            return redirect()->to('/users/edit/' . $id)->withInput();
        }

        // Update domain assignments
        if ($role === 'user') {
            $domainIds = $this->request->getPost('domains') ?? [];
            $this->usersModel->setUserDomains($id, $domainIds);
        } else {
            // Super users don't need domain assignments — clear them
            $this->usersModel->setUserDomains($id, []);
        }

        session()->setFlashdata('success', 'User updated successfully.');
        return redirect()->to('/users');
    }

    /**
     * Delete a user (POST).
     */
    public function delete($id = null)
    {
        if ($id === null) {
            return redirect()->to('/users');
        }

        // Prevent deleting yourself
        $currentUserId = session()->get('user_id');
        if ($id === $currentUserId) {
            session()->setFlashdata('error', 'You cannot delete your own account.');
            return redirect()->to('/users');
        }

        $success = $this->usersModel->delete($id);
        if ($success) {
            session()->setFlashdata('success', 'User deleted successfully.');
        } else {
            session()->setFlashdata('error', 'Failed to delete user.');
        }

        return redirect()->to('/users');
    }
}
