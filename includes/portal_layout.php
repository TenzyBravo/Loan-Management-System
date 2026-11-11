<?php
/**
 * Customer portal layout
 *
 * One shared shell for every logged-in customer page:
 *  - desktop: fixed sidebar on the left
 *  - phone:   slim top bar + bottom tab bar (thumb-reachable, no hamburger menu)
 *
 * Usage:
 *   require_once __DIR__ . '/includes/portal_layout.php';
 *   portal_header('My Loans', 'loans');           // before the page content
 *   ... page content ...
 *   portal_footer();                               // after the page content
 *
 * Options (both functions take an optional array):
 *   portal_header: 'head' => extra HTML for <head> (page-specific <style>, meta)
 *   portal_footer: 'scripts' => extra HTML after the shared scripts (page-specific <script>)
 *
 * Auth pages (login, register, password reset) use portal_auth_header()/portal_auth_footer().
 */

if (!function_exists('portal_nav_items')) {

    /** Navigation: key => [label, short label for the tab bar, icon, url] */
    function portal_nav_items(): array {
        return [
            'home'      => ['Dashboard',   'Home',      'fa-home',          'customer_dashboard.php'],
            'apply'     => ['Apply for Loan', 'Apply',  'fa-plus-circle',   'customer_apply_loan.php'],
            'loans'     => ['My Loans',    'Loans',     'fa-file-invoice-dollar', 'customer_my_loans.php'],
            'documents' => ['My Documents', 'Docs',     'fa-folder-open',   'customer_my_documents.php'],
            'profile'   => ['My Profile',  'Profile',   'fa-user',          'customer_profile.php'],
        ];
    }

    /** Shared <head> tags: fonts, Bootstrap 4.6, Font Awesome, portal theme. */
    function portal_head_assets(string $title, string $extraHead = ''): void {
        $t = htmlspecialchars($title, ENT_QUOTES);
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1a5f2a">
    <title>{$t} | Basils Money Lenders</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/portal.css?v=1">
{$extraHead}
</head>
HTML;
    }

    /** Opening shell for logged-in pages. $active is a key of portal_nav_items(). */
    function portal_header(string $title, string $active = '', array $opts = []): void {
        portal_head_assets($title, $opts['head'] ?? '');

        $name = trim((string) ($_SESSION['customer_name'] ?? ''));
        $first = $name !== '' ? explode(' ', $name)[0] : 'Customer';
        $initial = strtoupper(substr($first, 0, 1));
        $nameHtml = htmlspecialchars($name !== '' ? $name : 'Customer', ENT_QUOTES);
        $initialHtml = htmlspecialchars($initial, ENT_QUOTES);
        $titleHtml = htmlspecialchars($title, ENT_QUOTES);

        echo '<body class="portal">' . "\n";
        echo '<a class="skip-link" href="#portal-content">Skip to content</a>' . "\n";

        // Desktop sidebar
        echo '<aside class="portal-sidebar" aria-label="Main menu">' . "\n";
        echo '  <a class="portal-brand" href="customer_dashboard.php"><span class="portal-brand-mark">B</span><span>Basils <em>Money Lenders</em></span></a>' . "\n";
        echo '  <nav class="portal-nav">' . "\n";
        foreach (portal_nav_items() as $key => [$label, $short, $icon, $url]) {
            $cls = $key === $active ? ' active' : '';
            $aria = $key === $active ? ' aria-current="page"' : '';
            echo "    <a class=\"portal-nav-link{$cls}\" href=\"{$url}\"{$aria}><i class=\"fas {$icon} fa-fw\"></i><span>{$label}</span></a>\n";
        }
        echo '  </nav>' . "\n";
        echo '  <div class="portal-sidebar-foot">' . "\n";
        echo "    <div class=\"portal-user\"><span class=\"portal-avatar\">{$initialHtml}</span><span class=\"portal-user-name\">{$nameHtml}</span></div>\n";
        echo '    <a class="portal-nav-link portal-logout" href="customer_logout.php"><i class="fas fa-sign-out-alt fa-fw"></i><span>Log out</span></a>' . "\n";
        echo '  </div>' . "\n";
        echo '</aside>' . "\n";

        // Phone top bar
        echo '<header class="portal-topbar">' . "\n";
        echo '  <a class="portal-brand portal-brand-sm" href="customer_dashboard.php"><span class="portal-brand-mark">B</span><span>Basils</span></a>' . "\n";
        echo "  <span class=\"portal-topbar-title\">{$titleHtml}</span>\n";
        echo '  <a class="portal-topbar-action" href="customer_logout.php" aria-label="Log out"><i class="fas fa-sign-out-alt"></i></a>' . "\n";
        echo '</header>' . "\n";

        echo '<main class="portal-main" id="portal-content">' . "\n";
        echo '<div class="portal-container">' . "\n";
    }

    /** Closing shell for logged-in pages: phone tab bar + shared scripts. */
    function portal_footer(array $opts = []): void {
        echo "</div>\n</main>\n";

        // Phone bottom tab bar (same pages as the sidebar)
        $current = basename($_SERVER['SCRIPT_NAME'] ?? '');
        echo '<nav class="portal-tabbar" aria-label="Main menu">' . "\n";
        foreach (portal_nav_items() as $key => [$label, $short, $icon, $url]) {
            $isActive = $url === $current;
            $cls = $isActive ? ' active' : '';
            $aria = $isActive ? ' aria-current="page"' : '';
            echo "  <a class=\"portal-tab{$cls}\" href=\"{$url}\"{$aria}><i class=\"fas {$icon}\"></i><span>{$short}</span></a>\n";
        }
        echo '</nav>' . "\n";

        portal_scripts();
        echo ($opts['scripts'] ?? '') . "\n";
        echo "</body>\n</html>\n";
    }

    /** jQuery + Bootstrap 4.6 bundle (shared by every portal page). */
    function portal_scripts(): void {
        echo '<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>' . "\n";
        echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>' . "\n";
    }

    /** Opening shell for login / register / password pages (centered card, no menu). */
    function portal_auth_header(string $title, array $opts = []): void {
        portal_head_assets($title, $opts['head'] ?? '');
        $wide = !empty($opts['wide']) ? ' auth-card-wide' : '';
        echo '<body class="portal portal-auth">' . "\n";
        echo '<main class="auth-shell" id="portal-content">' . "\n";
        echo '  <a class="portal-brand auth-brand" href="index.php"><span class="portal-brand-mark">B</span><span>Basils <em>Money Lenders</em></span></a>' . "\n";
        echo "  <div class=\"auth-card{$wide}\">\n";
    }

    /** Closing shell for auth pages. */
    function portal_auth_footer(array $opts = []): void {
        echo "  </div>\n";
        echo '  <p class="auth-footnote"><a href="index.php"><i class="fas fa-arrow-left"></i> Back to website</a></p>' . "\n";
        echo "</main>\n";
        portal_scripts();
        echo ($opts['scripts'] ?? '') . "\n";
        echo "</body>\n</html>\n";
    }
}
