<?php
declare(strict_types=1);
use App\Controllers\{HomeController as H, AuthController as A, AccountController as Ac, WardrobeController as W, MediaController as M,
    StudioController as S, RequestController as R, OrderController as O, AdminController as Ad, DesignerController as D,
    MarketController as Mk, StyleController as St};

/** @var App\Core\Router $router */
$router->get('/', [H::class, 'home']);
$router->post('/locale', [H::class, 'locale']);
$router->get('/learn', [H::class, 'learn']);
$router->get('/privacy', [H::class, 'privacy']);
$router->get('/terms', [H::class, 'terms']);
$router->get('/contact', [H::class, 'contact']);

$router->get('/login', [A::class, 'loginForm']);
$router->post('/login', [A::class, 'login']);
$router->get('/register', [A::class, 'registerForm']);
$router->post('/register', [A::class, 'register']);
$router->get('/verify-email', [A::class, 'verify']);
$router->get('/forgot-password', [A::class, 'forgotForm']);
$router->post('/forgot-password', [A::class, 'forgot']);
$router->get('/reset-password', [A::class, 'resetForm']);
$router->post('/reset-password', [A::class, 'reset']);
$router->post('/logout', [A::class, 'logout'], 'auth');

$router->get('/account', [Ac::class, 'show'], 'auth');
$router->post('/account', [Ac::class, 'update'], 'auth');

$router->get('/wardrobe', [W::class, 'index'], 'auth');
$router->get('/wardrobe/new', [W::class, 'create'], 'auth');
$router->post('/wardrobe', [W::class, 'store'], 'auth');
$router->get('/wardrobe/{id}', [W::class, 'show'], 'auth');
$router->get('/wardrobe/{id}/edit', [W::class, 'edit'], 'auth');
$router->post('/wardrobe/{id}', [W::class, 'update'], 'auth');
$router->post('/wardrobe/{id}/delete', [W::class, 'destroy'], 'auth');
$router->post('/wardrobe/{id}/images', [W::class, 'addImages'], 'auth');
$router->post('/images/{id}/delete', [W::class, 'deleteImage'], 'auth');
$router->get('/wardrobe/{id}/assess', [W::class, 'assessForm'], 'auth');
$router->post('/wardrobe/{id}/assess', [W::class, 'assess'], 'auth');
$router->get('/assessments/{id}', [W::class, 'assessment'], 'auth');
$router->get('/media/garment/{id}', [M::class, 'garmentImage'], 'auth');
$router->get('/media/slip/{id}', [M::class, 'slip'], 'auth');

// Rewear marketplace & Swap (members only)
$router->get('/market', [Mk::class, 'index'], 'auth');
$router->get('/wardrobe/{id}/sell', [Mk::class, 'sellForm'], 'auth');
$router->post('/wardrobe/{id}/sell', [Mk::class, 'sell'], 'auth');
$router->get('/market/{id}', [Mk::class, 'show'], 'auth');
$router->post('/market/{id}/buy', [Mk::class, 'buy'], 'auth');
$router->post('/market/{id}/status', [Mk::class, 'status'], 'auth');
$router->post('/market-offers/{id}/decide', [Mk::class, 'decide'], 'auth');
$router->post('/market-offers/{id}/cancel', [Mk::class, 'cancelOffer'], 'auth');
$router->get('/swap', [Mk::class, 'swapIndex'], 'auth');
$router->get('/wardrobe/{id}/swap', [Mk::class, 'swapForm'], 'auth');
$router->post('/wardrobe/{id}/swap', [Mk::class, 'swapCreate'], 'auth');
$router->get('/swap/{id}', [Mk::class, 'swapShow'], 'auth');
$router->post('/swap/{id}/propose', [Mk::class, 'swapPropose'], 'auth');
$router->post('/swap/{id}/withdraw', [Mk::class, 'swapWithdraw'], 'auth');
$router->post('/swap-requests/{id}', [Mk::class, 'swapAct'], 'auth');

// Style, favourites, notifications
$router->get('/style', [St::class, 'style'], 'auth');
$router->post('/style/outfits', [St::class, 'saveOutfit'], 'auth');
$router->post('/outfits/{id}/delete', [St::class, 'deleteOutfit'], 'auth');
$router->post('/favorites', [St::class, 'favorite'], 'auth');
$router->get('/saved', [St::class, 'saved'], 'auth');
$router->get('/inbox', [St::class, 'inbox'], 'auth');
$router->post('/inbox/seen', [St::class, 'markSeen'], 'auth');

$router->get('/studio', [S::class, 'index']);
$router->post('/studio', [S::class, 'store'], 'auth');

$router->get('/requests', [R::class, 'index'], 'auth');
$router->get('/requests/{id}', [R::class, 'show'], 'auth');
$router->post('/requests/{id}/transition', [R::class, 'transition'], 'auth');
$router->post('/requests/{id}/message', [R::class, 'message'], 'auth');
$router->post('/quotations/{id}/respond', [R::class, 'respond'], 'auth');

$router->get('/orders/{id}', [O::class, 'show'], 'auth');
$router->post('/orders/{id}/transition', [O::class, 'transition'], 'auth');
$router->post('/orders/{id}/slip', [O::class, 'slip'], 'auth');

$router->get('/admin', [Ad::class, 'overview'], 'admin');
$router->get('/admin/requests', [Ad::class, 'requests'], 'admin');
$router->get('/admin/requests/{id}', [Ad::class, 'request'], 'admin');
$router->post('/admin/requests/{id}/assign', [Ad::class, 'assign'], 'admin');
$router->get('/admin/orders', [Ad::class, 'orders'], 'admin');
$router->get('/admin/orders/{id}', [Ad::class, 'order'], 'admin');
$router->post('/admin/orders/{id}/payment', [Ad::class, 'payment'], 'admin');
$router->get('/admin/users', [Ad::class, 'users'], 'admin');
$router->post('/admin/users/grant', [Ad::class, 'grant'], 'admin');
$router->get('/admin/audit', [Ad::class, 'audit'], 'admin');
$router->get('/admin/moderation', [Ad::class, 'moderation'], 'admin');
$router->post('/admin/moderate', [Ad::class, 'moderate'], 'admin');
$router->get('/admin/settings', [Ad::class, 'settings'], 'admin');
$router->post('/admin/settings', [Ad::class, 'saveSettings'], 'admin');
$router->post('/admin/designers/{id}', [Ad::class, 'designerProfile'], 'admin');

$router->get('/designer', [D::class, 'index'], 'designer');
$router->get('/designer/requests/{id}', [D::class, 'show'], 'designer');
$router->post('/designer/requests/{id}/proposal', [D::class, 'proposal'], 'designer');
