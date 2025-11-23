<?php

require_once __DIR__ . '/utils.php';

function render_page(string $pageFile, array $data = []) {
    $pagePath = __DIR__ . "/../pages/$pageFile";
    
    if (!preg_match('/^[a-z0-9_-]+\.php$/i', $pageFile) || !file_exists($pagePath)) {
        http_response_code(404);
        echo "Page not found";
        die();
    }

    return render_page_unsafe($pagePath, $data);
}

function render_page_unsafe(string $pagePath, array $data = []): void {
    render_page_fn(function (array $data) use ($pagePath) {
        extract($data, EXTR_SKIP);

        require $pagePath;
    }, $data);
}

function render_page_fn(callable $fn, array $data = []): void
{
    foreach ($data as $key => $value) {
        $data[$key] = htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    $title = $data['title'] ?? 'Finance App';

    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="stylesheet" href="/assets/style.css?<?= random_int(0, PHP_INT_MAX)?>">
    <!-- Montserrat, Poppins, Inter, Roboto -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700&family=Poppins:wght@700&family=Inter:wght@400&family=Roboto:wght@400&display=swap" rel="stylesheet">

    <script src="/assets/script.js?<?= random_int(0, PHP_INT_MAX)?>"></script>
</head>
<body>
    <div class="layout">
            <?php try { call_user_func($fn, $data); } catch (Exception $e) {var_dump($e); echo "Une erreur est survenue";}?>
    </div>
</body>
</html>
<?php
}