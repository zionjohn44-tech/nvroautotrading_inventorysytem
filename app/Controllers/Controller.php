<?php
declare(strict_types=1);

namespace NVRO\Controllers;

use NVRO\Core\HttpException;
use NVRO\Core\Session;
use NVRO\Models\User;
use NVRO\Repositories\RepositoryInterface;
use NVRO\Validation\ValidationException;
use PDOException;

abstract class Controller
{
    protected ?User $user;
    public function __construct(?User $user = null) { $this->user = $user; }

    protected function render(string $view, array $viewData = [], bool $standalone = false): void
    {
        $user = $this->user;
        $flash = Session::takeFlash();
        extract($viewData, EXTR_SKIP);
        if ($standalone) { require dirname(__DIR__, 2) . '/views/' . $view . '.php'; return; }
        ob_start();
        require dirname(__DIR__, 2) . '/views/' . $view . '.php';
        $content = ob_get_clean();
        require dirname(__DIR__, 2) . '/views/layout.php';
    }

    protected function redirect(string $route, string $message = '', array $parameters = []): void
    {
        if ($message !== '') { Session::flash($message); }
        header('Location: ' . \url($route, $parameters), true, 303);
        exit;
    }

    protected function id(): int
    {
        $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$id || $id < 1) { throw new HttpException(404, 'Record not found.'); }
        return $id;
    }

    protected function record(RepositoryInterface $repository, int $id): array
    {
        $row = $repository->find($id);
        if (!$row) { throw new HttpException(404, 'Record not found.'); }
        return $row;
    }

    protected function form(array $config, callable $save, bool $standalone = false): void
    {
        $errors = [];
        $data = $config['data'] ?? [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = array_replace($data, $_POST);
            try {
                $save($_POST);
                $this->redirect($config['returnRoute'], $config['success']);
            } catch (ValidationException $exception) {
                $errors = $exception->errors;
                http_response_code(422);
            } catch (PDOException $exception) {
                if ($exception->getCode() !== '23000') { throw $exception; }
                $errors = ['record' => 'This record conflicts with an existing or linked record. Check the details and try again.'];
                http_response_code(422);
            }
        }
        $this->render($standalone ? 'setup' : 'form', array_replace($config, compact('data', 'errors')), $standalone);
    }

    protected function action(callable $action, string $returnRoute, string $success, array $parameters = []): void
    {
        try {
            $action();
            Session::flash($success);
        } catch (ValidationException $exception) {
            Session::flash($exception->getMessage(), 'error');
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') { throw $exception; }
            Session::flash('This record is linked to other records and cannot be removed.', 'error');
        }
        $this->redirect($returnRoute, '', $parameters);
    }

    protected function paginate(array $rows): array
    {
        $total = count($rows);
        $pages = max(1, (int) ceil($total / 12));
        $page = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
        return ['rows' => array_slice($rows, ($page - 1) * 12, 12), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }
}
