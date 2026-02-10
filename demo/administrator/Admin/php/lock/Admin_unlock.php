<?php
session_start();
require_once '../../../../connect/db_connect.php';

// ログインチェック
if (!isset($_SESSION['admin_user'])) {
    header("Location: ../../login.php");
    exit;
}

// CSRF トークン生成・検証
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf_token = $_SESSION['csrf_token'];

// 管理者チェック
if (!isset($_SESSION['admin_user'])) {
    $isAjax = (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
        || (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false);
    if ($isAjax) {
        // 出力バッファをクリアしてヘッダをセット
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);

        // ログ用に安全にユーザ情報を整形
        $adminUser = $_SESSION['admin_user'] ?? '';
        $adminUserForLog = is_array($adminUser) ? json_encode($adminUser, JSON_UNESCAPED_UNICODE) : (string)$adminUser;
        error_log("Unauthorized access attempt by user={$adminUserForLog}");

        // エラーを返す（成功フラグは false にする）
        echo json_encode(['success' => false, 'error' => '権限がありません'], JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        header('Location: ../login.php');
        exit;
    }
}

// JSON POST（AJAX）処理
$raw = file_get_contents('php://input');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $raw !== '') {
    header('Content-Type: application/json; charset=utf-8');

    // JSONパース
    $input = json_decode($raw, true);
    if ($input === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'JSONパースエラー']);
        exit;
    }

    // CSRF トークン検証
    $client_csrf = $input['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], (string)$client_csrf)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'CSRFトークン不正']);
        exit;
    }

    // 入力検証
    $id = isset($input['id']) ? (int)$input['id'] : 0;
    $set_locked = array_key_exists('set_locked', $input) ? (int)$input['set_locked'] : null;
    if ($id <= 0 || ($set_locked !== 0 && $set_locked !== 1)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'パラメータ不正']);
        exit;
    }

    // DB更新処理
    try {
        // 存在確認
        $stmt = $pdo->prepare("SELECT is_locked FROM tournaments WHERE id = :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$current) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => '大会が見つかりません']);
            exit;
        }

        // 更新（updated_at カラムがある前提）
        $upd = $pdo->prepare("UPDATE tournaments SET is_locked = :newState, updated_at = NOW() WHERE id = :id");
        $upd->bindValue(':newState', $set_locked, PDO::PARAM_INT);
        $upd->bindValue(':id', $id, PDO::PARAM_INT);
        $ok = $upd->execute();

        if ($ok) {
            // 出力バッファをクリアしてヘッダ（念のため）
            while (ob_get_level()) ob_end_clean();
            header('Content-Type: application/json; charset=utf-8');

            // ログ用に安全にユーザ情報を整形
            $adminUser = $_SESSION['admin_user'] ?? '';
            $adminUserForLog = is_array($adminUser) ? json_encode($adminUser, JSON_UNESCAPED_UNICODE) : (string)$adminUser;

            echo json_encode(['success' => true, 'is_locked' => $set_locked], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            $err = $pdo->errorInfo();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'DB更新に失敗しました']);
            exit;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'サーバエラーが発生しました']);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => '予期せぬエラーが発生しました']);
        exit;
    }
}

// GET 表示処理
$q = trim($_GET['q'] ?? '');
$sql = "SELECT id, title, is_locked, event_date FROM tournaments
        WHERE ( :q_empty = 1 OR id = :id_exact OR title LIKE :q_like )
        ORDER BY id DESC
        LIMIT 200";
$stmt = $pdo->prepare($sql);
$id_exact = is_numeric($q) ? (int)$q : 0;
$q_like = '%' . $q . '%';
$stmt->bindValue(':q_empty', $q === '' ? 1 : 0, PDO::PARAM_INT);
$stmt->bindValue(':id_exact', $id_exact, PDO::PARAM_INT);
$stmt->bindValue(':q_like', $q_like, PDO::PARAM_STR);
$stmt->execute();
$tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="ja">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>大会のロック管理</title>
    <link rel="stylesheet" href="./Admin_unlock.css">
</head>

<body>
    <div class="container">
        <header class="header">
            <nav class="breadcrumb">メニュー ＞ 大会ロック管理 ＞</nav>
            <h1 class="title">大会のロック管理</h1>
        </header>

        <form id="searchForm" class="search-area" method="GET" action="">
            <input id="q" name="q" type="search" class="search-input" placeholder="🔍 ID または大会名" value="<?= htmlspecialchars($q) ?>">
            <button type="submit" class="search-btn">検索</button>
        </form>

        <main class="list-container" id="listContainer" aria-live="polite">
            <?php if (empty($tournaments)): ?>
                <div class="empty">該当する大会はありません</div>
            <?php else: ?>
                <?php foreach ($tournaments as $t): ?>
                    <div class="list-item" data-id="<?= htmlspecialchars($t['id']) ?>">
                        <div class="col-id">ID <?= htmlspecialchars($t['id']) ?></div>
                        <div class="col-title"><?= htmlspecialchars($t['title']) ?></div>
                        <div class="col-status">
                            <?php if ((int)$t['is_locked'] === 1): ?>
                                <span class="lock-status locked">ロック中 🔒</span>
                            <?php else: ?>
                                <span class="lock-status unlocked">解除済み</span>
                            <?php endif; ?>
                        </div>
                        <div class="col-action">
                            <label class="switch" title="クリックで切替">
                                <!-- checked が true のときに「解除（右）」になる -->
                                <input type="checkbox" class="toggle-input" <?= ((int)$t['is_locked'] === 0) ? 'checked' : '' ?> aria-checked="<?= ((int)$t['is_locked'] === 0) ? 'true' : 'false' ?>">
                                <span class="slider"></span>
                            </label>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </main>

        <div style="margin-top:12px;text-align:right">
            <a class="btn-back" href="../Admin_top.php">戻る</a>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script>
        (async function() {
            const list = document.getElementById('listContainer');
            const toast = document.getElementById('toast');
            const csrfToken = <?= json_encode($csrf_token) ?>;

            function showToast(message, type = 'success') {
                toast.textContent = message;
                toast.className = 'toast ' + (type === 'success' ? 'success' : 'error');
                toast.style.display = 'block';
                setTimeout(() => {
                    toast.style.display = 'none';
                }, 3500);
            }

            // list.addEventListener('change', async (e) => {
            //     const input = e.target;
            //     if (!input.classList.contains('toggle-input')) return;

            //     const item = input.closest('.list-item');
            //     const id = item?.dataset?.id;
            //     if (!id) return;

            //     const newUnlocked = input.checked; // checked === unlocked
            //     const set_locked = newUnlocked ? 0 : 1;
            //     const action = newUnlocked ? '解除' : 'ロック';
            //     if (!confirm(`大会ID ${id} を ${action} しますか？`)) {
            //         input.checked = !newUnlocked;
            //         return;
            //     }

                input.disabled = true;

                try {
                    const res = await fetch(window.location.pathname, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id: parseInt(id, 10),
                            set_locked: set_locked,
                            csrf_token: csrfToken
                        })
                    });

                    // HTTP ステータスが OK でない場合は本文を取得してユーザー向けに表示（console は出さない）
                    if (!res.ok) {
                        const text = await res.text();
                        let msg = `HTTP ${res.status}`;
                        try {
                            const parsed = JSON.parse(text);
                            msg = parsed.error || msg;
                        } catch {
                            // HTML やプレーンテキストが返ってきた場合は先頭だけ見せる
                            msg = text.slice(0, 200);
                        }
                        showToast(msg, 'error');
                        input.checked = !newUnlocked;
                        input.disabled = false;
                        return;
                    }

                    // 正常レスポンスを JSON として扱う
                    const data = await res.json();
                    if (data && data.success) {
                        const statusEl = item.querySelector('.lock-status');
                        if (data.is_locked === 1) {
                            statusEl.textContent = 'ロック中 🔒';
                            statusEl.classList.remove('unlocked');
                            statusEl.classList.add('locked');
                            input.setAttribute('aria-checked', 'false');
                        } else {
                            statusEl.textContent = '解除済み';
                            statusEl.classList.remove('locked');
                            statusEl.classList.add('unlocked');
                            input.setAttribute('aria-checked', 'true');
                        }
                        showToast('更新しました', 'success');
                    } else {
                        showToast(data?.error || '処理に失敗しました', 'error');
                        input.checked = !newUnlocked;
                    }
                } catch (err) {
                    // ここでも console に出さず、ユーザーにだけ通知する
                    showToast('通信エラーが発生しました', 'error');
                    input.checked = !newUnlocked;
                } finally {
                    input.disabled = false;
                }
            });
        })();
    </script>
</body>

</html>