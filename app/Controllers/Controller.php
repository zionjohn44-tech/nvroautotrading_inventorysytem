<?php
declare(strict_types=1);

namespace NVRO\Controllers;

use NVRO\Core\HttpException;
use NVRO\Core\Session;
use NVRO\Models\Entity;
use NVRO\Models\User;
use NVRO\Repositories\RepositoryInterface;
use NVRO\Validation\ValidationException;
use PDOException;

/**
 * ABSTRACTION + INHERITANCE: base class of every controller. It holds the
 * signed-in user and the request helpers (render, redirect, forms, actions,
 * pagination) that each module controller inherits instead of repeating.
 */
abstract class Controller
{
    private const PAGE_SIZE = 12;

    protected ?User $user;

    public function __construct(?User $user = null)
    {
        $this->user = $user;
    }

    /** Renders a view inside the workspace layout, or on its own when $standalone. */
    protected function render(string $view, array $viewData = [], bool $standalone = false): void
    {
        $user = $this->user;
        $flash = Session::takeFlash();
        extract($viewData, EXTR_SKIP);
        if ($standalone) {
            require dirname(__DIR__, 2) . '/views/' . $view . '.php';
            return;
        }
        ob_start();
        require dirname(__DIR__, 2) . '/views/' . $view . '.php';
        $content = ob_get_clean();
        require dirname(__DIR__, 2) . '/views/layout.php';
    }

    /** Sends a 303 redirect (after a form post) with an optional flash message. */
    protected function redirect(string $route, string $message = '', array $parameters = []): void
    {
        if ($message !== '') {
            Session::flash($message);
        }
        header('Location: ' . \url($route, $parameters), true, 303);
        exit;
    }

    /** The positive record ID from ?id=, or a 404 error. */
    protected function id(): int
    {
        $id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            throw new HttpException(404, 'Record not found.');
        }
        return $id;
    }

    /**
     * POLYMORPHISM: works with any repository. The same find() call runs the
     * vehicle, inquiry, test-drive, customer or user query depending on the object passed.
     */
    protected function record(RepositoryInterface $repository, int $id): array
    {
        $row = $repository->find($id);
        if (!$row) {
            throw new HttpException(404, 'Record not found.');
        }
        return $row;
    }

    /**
     * Turns entity objects into dropdown options [id => label].
     * POLYMORPHISM: each entity type supplies its own describe() label.
     *
     * @param Entity[] $entities
     */
    protected function options(array $entities): array
    {
        $options = [];
        foreach ($entities as $entity) {
            $options[$entity->getId()] = $entity->describe();
        }
        return $options;
    }

    /** Shows a form; on POST runs $save, then redirects or re-shows the errors. */
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
                if ($exception->getCode() !== '23000') {
                    throw $exception;
                }
                $errors = ['record' => 'This record conflicts with an existing or linked record. Check the details and try again.'];
                http_response_code(422);
            }
        }
        $this->render($standalone ? 'setup' : 'form', array_replace($config, compact('data', 'errors')), $standalone);
    }

    /** Runs a one-click POST action (delete, status change) and redirects with a message. */
    protected function action(callable $action, string $returnRoute, string $success, array $parameters = []): void
    {
        try {
            $action();
            Session::flash($success);
        } catch (ValidationException $exception) {
            Session::flash($exception->getMessage(), 'error');
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }
            Session::flash('This record is linked to other records and cannot be removed.', 'error');
        }
        $this->redirect($returnRoute, '', $parameters);
    }

    /** Splits rows into pages of 12 using ?page=. */
    protected function paginate(array $rows): array
    {
        $total = count($rows);
        $pages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $page = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
        return [
            'rows' => array_slice($rows, ($page - 1) * self::PAGE_SIZE, self::PAGE_SIZE),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }
}
