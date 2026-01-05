<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>大会登録</title>
    <link rel="stylesheet" href="../css/Admin_registration.css">
    <style>
        .tournament-info-section {
            background-color: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .tournament-info-section h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #1f2937;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 0.75rem;
        }
        .form-grid {
            display: grid;
            gap: 1.5rem;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #374151;
        }
        .form-group label .required {
            color: #dc2626;
            margin-left: 0.25rem;
        }
        .form-input {
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: #2563eb;
        }
        .button-container {
            display: flex;
            gap: 2rem;
            justify-content: center;
            margin-top: 3rem;
        }
        .action-button {
            padding: 0.875rem 3rem;
            font-size: 1.125rem;
            background-color: white;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .action-button:hover {
            background-color: #f9fafb;
        }
    </style>
</head>
<body>
    <div class="breadcrumb">
        <a href="master2.php" class="breadcrumb-link">メニュー></a>
        <a href="tournament-list.php" class="breadcrumb-link">大会登録・名称変更></a>
        <a href="#" class="breadcrumb-link">大会登録></a>
    </div>
    
    <div class="container">
        <!-- 大会情報入力セクション -->
        <div class="tournament-info-section">
            <h2>大会情報</h2>
            <div class="form-grid">
                <div class="form-group">
                    <label>大会名称<span class="required">*</span></label>
                    <input type="text" id="tournament-name" class="form-input" placeholder="例：春季大会" required>
                </div>
                
                <div class="form-group">
                    <label>大会会場<span class="required">*</span></label>
                    <input type="text" id="venue" class="form-input" placeholder="例：県立体育館" required>
                </div>
                
                <div class="form-group">
                    <label>大会開催日<span class="required">*</span></label>
                    <input type="date" id="event-date" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label>大会パスワード<span class="required">*</span></label>
                    <input type="password" id="tournament-password" class="form-input" placeholder="パスワードを入力" required>
                </div>
                
                <div class="form-group">
                    <label>大会パスワード（再入力）<span class="required">*</span></label>
                    <input type="password" id="tournament-password-confirm" class="form-input" placeholder="パスワードを再入力" required>
                </div>
                
                <div class="form-group">
                    <label>補助員記録係のパスワード<span class="required">*</span></label>
                    <input type="password" id="assistant-password" class="form-input" placeholder="パスワードを入力" required>
                </div>
                
                <div class="form-group">
                    <label>補助員記録係のパスワード（再入力）<span class="required">*</span></label>
                    <input type="password" id="assistant-password-confirm" class="form-input" placeholder="パスワードを再入力" required>
                </div>
                
                <div class="form-group">
                    <label>試合会場数<span class="required">*</span></label>
                    <select id="court-count" class="form-input" required>
                        <option value="">選択してください</option>
                        <option value="4">4試合場</option>
                        <option value="5">5試合場</option>
                        <option value="6">6試合場</option>
                        <option value="7">7試合場</option>
                        <option value="8">8試合場</option>
                        <option value="9">9試合場</option>
                        <option value="10">10試合場</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="button-container">
            <button class="action-button" onclick="location.href='Admin_selection.php'">戻る</button>
            <button class="action-button" id="registerButton">確認画面へ</button>
        </div>
    </div>
    
    <script>
        console.log('=== 大会登録画面 ===');
        
        const registerButton = document.getElementById('registerButton');
        console.log('registerButton:', registerButton);
        
        if (registerButton) {
            registerButton.addEventListener('click', function() {
                console.log('=== 確認画面へボタンクリック ===');
                
                // 大会情報を取得
                const tournamentName = document.getElementById('tournament-name').value;
                const venue = document.getElementById('venue').value;
                const eventDate = document.getElementById('event-date').value;
                const courtCount = document.getElementById('court-count').value;
                const tournamentPassword = document.getElementById('tournament-password').value;
                const tournamentPasswordConfirm = document.getElementById('tournament-password-confirm').value;
                const assistantPassword = document.getElementById('assistant-password').value;
                const assistantPasswordConfirm = document.getElementById('assistant-password-confirm').value;
                
                console.log('入力値:', {
                    tournamentName,
                    venue,
                    eventDate,
                    courtCount
                });
                
                // 必須項目チェック
                if (!tournamentName) {
                    alert('大会名称を入力してください');
                    return;
                }
                if (!venue) {
                    alert('大会会場を入力してください');
                    return;
                }
                if (!eventDate) {
                    alert('大会開催日を選択してください');
                    return;
                }
                if (!courtCount) {
                    alert('試合会場数を選択してください');
                    return;
                }
                if (!tournamentPassword) {
                    alert('大会パスワードを入力してください');
                    return;
                }
                if (!tournamentPasswordConfirm) {
                    alert('大会パスワード（再入力）を入力してください');
                    return;
                }
                if (!assistantPassword) {
                    alert('補助員記録係のパスワードを入力してください');
                    return;
                }
                if (!assistantPasswordConfirm) {
                    alert('補助員記録係のパスワード（再入力）を入力してください');
                    return;
                }
                
                // パスワード一致確認
                if (tournamentPassword !== tournamentPasswordConfirm) {
                    alert('大会パスワードが一致しません');
                    return;
                }
                
                if (assistantPassword !== assistantPasswordConfirm) {
                    alert('補助員記録係のパスワードが一致しません');
                    return;
                }
                
                // 大会情報をオブジェクトにまとめる
                const tournamentData = {
                    tournament_name: tournamentName,
                    venue: venue,
                    event_date: eventDate,
                    court_count: courtCount,
                    tournament_password: tournamentPassword,
                    assistant_password: assistantPassword
                };
                
                console.log('保存するデータ:', tournamentData);
                
                // localStorageに保存
                try {
                    localStorage.setItem('tournamentData', JSON.stringify(tournamentData));
                    console.log('✓ データ保存完了');
                    
                    // 確認画面に遷移
                    location.href = 'tournament-register-confirm2.php';
                } catch (error) {
                    console.error('✗ 保存エラー:', error);
                    alert('データの保存に失敗しました: ' + error.message);
                }
            });
        } else {
            console.error('registerButtonが見つかりません');
        }
    </script>
</body>
</html>