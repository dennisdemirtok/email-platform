<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class SuperMiddleware implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // Must be logged in (should already be checked by auth filter, but just in case)
        if (!$session->has('isLoggedIn')) {
            return redirect()->to('/login');
        }

        // Must be super user
        $role = $session->get('user_role') ?? '';
        if ($role !== 'super') {
            $session->setFlashdata('error', 'You do not have permission to access that page.');
            return redirect()->to('/');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing needed after
    }
}
