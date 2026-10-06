<?php

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT
        id,
        username,
        display_name,
        citizen,
        role,
        reputation,
        avatar,
        wallet_address,
        status
    FROM users
    WHERE id = :user_id
    LIMIT 1
");

$stmt->execute([
    ':user_id' => $userId
]);

$currentUser = $stmt->fetch();

if (!$currentUser || $currentUser['status'] !== 'active') {

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {

        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'domain' => $params['domain'] ?? '',
                'secure' => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax'
            ]
        );
    }

    session_destroy();

    header('Location: ../login.html?error=session');
    exit;
}

$_SESSION['user_id'] = (int) $currentUser['id'];
$_SESSION['username'] = $currentUser['username'];
$_SESSION['display_name'] = $currentUser['display_name'];
$_SESSION['citizen'] = (int) $currentUser['citizen'];
$_SESSION['role'] = $currentUser['role'];
$_SESSION['reputation'] = (int) $currentUser['reputation'];
$_SESSION['avatar'] = $currentUser['avatar'];
$_SESSION['wallet_address'] = $currentUser['wallet_address'];

/*
|--------------------------------------------------------------------------
| Global ConnectID message notification
|--------------------------------------------------------------------------
|
| Every authenticated HTML page receives the same unread-message
| indicator. Backend JSON responses are left untouched.
|
*/

if (
    PHP_SAPI !== 'cli' &&
    !ob_get_level()
) {

    ob_start(function (string $buffer): string {

        /*
         * Only modify actual HTML pages.
         */
        if (
            stripos($buffer, '</body>') === false ||
            stripos($buffer, '<html') === false
        ) {
            return $buffer;
        }

        $script = <<<'HTML'

<style>
.connectid-message-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 18px;
    height: 18px;
    padding: 0 6px;
    margin-left: 6px;
    border-radius: 999px;
    background: #ff7a00;
    color: #ffffff;
    font-size: 11px;
    font-weight: 700;
    line-height: 18px;
    vertical-align: middle;
}

.connectid-message-badge.hidden {
    display: none;
}
</style>

<script>
(function () {

    'use strict';

    let previousUnreadCount = null;

    function findMessagesLinks() {

        return Array.from(
            document.querySelectorAll('a')
        ).filter(function (link) {

            const href = link.getAttribute('href') || '';

            return (
                href === 'messages.php' ||
                href === './messages.php' ||
                href.endsWith('/messages.php')
            );

        });
    }

    function updateMessagesBadges(totalUnread) {

        const links = findMessagesLinks();

        links.forEach(function (link) {

            let badge = link.querySelector(
                '.connectid-message-badge'
            );

            if (!badge) {

                badge = document.createElement('span');

                badge.className =
                    'connectid-message-badge hidden';

                link.appendChild(badge);
            }

            if (totalUnread > 0) {

                badge.textContent =
                    totalUnread > 99
                        ? '99+'
                        : String(totalUnread);

                badge.classList.remove('hidden');

                link.setAttribute(
                    'aria-label',
                    'Messages: ' + totalUnread + ' unread'
                );

            } else {

                badge.textContent = '';

                badge.classList.add('hidden');

                link.removeAttribute('aria-label');
            }
        });
    }

    async function refreshMessageStatus() {

        try {

            const response = await fetch(
                'backend/message_status.php?_=' +
                Date.now(),
                {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (
                !data ||
                data.success !== true
            ) {
                return;
            }

            const totalUnread =
                parseInt(
                    data.total_unread,
                    10
                ) || 0;

            updateMessagesBadges(totalUnread);

            /*
             * If unread count increases while the user is
             * on another page, update the browser title.
             */
            if (
                previousUnreadCount !== null &&
                totalUnread > previousUnreadCount
            ) {

                const baseTitle =
                    document.title
                        .replace(/^\(\d+\)\s*/, '');

                document.title =
                    '(' +
                    totalUnread +
                    ') ' +
                    baseTitle;
            }

            if (
                previousUnreadCount !== null &&
                totalUnread === 0
            ) {

                document.title =
                    document.title
                        .replace(/^\(\d+\)\s*/, '');
            }

            previousUnreadCount =
                totalUnread;

        } catch (error) {

            /*
             * Ignore temporary polling failures.
             */
        }
    }

    /*
     * Run immediately.
     */
    refreshMessageStatus();

    /*
     * Keep the global indicator updated.
     */
    setInterval(
        refreshMessageStatus,
        3000
    );

})();
</script>

HTML;

        return str_ireplace(
            '</body>',
            $script . "\n</body>",
            $buffer
        );
    });
}
