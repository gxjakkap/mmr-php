<?= App\Core\View::partial('partials/empty', ['title' => $title, 'body' => $body ?? '', 'action' => '<a class="btn btn-outline" href="/">' . e(t('nav.home')) . '</a>']) ?>
