<?php 

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AuthMiddleware implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Vérifiez si l'utilisateur est connecté
        $session = session();
        if (!$session->has('isLoggedIn')) {
            // L'utilisateur n'est pas connecté, redirigez-le vers la page de connexion
            return redirect()->to('/login');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Vous pouvez implémenter des actions après l'exécution du contrôleur si nécessaire
    }
}
