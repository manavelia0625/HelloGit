<?php
	/* 
	 * 変数の値を変更するときは直接変更するのではなく、
	 * 必ず変更箇所をコピーし、変更前をコメントアウトして残すようにしてください。
	 * コメントには変更した日付と変更者を残すようにしてください。
	*/
	session_start();
	$incret = include_once("./lib/common_constant.php");
	$incret = include_once("./lib/common_function.php");
	$incret = include_once("./lib/database.class.php");

	$user_id = "";									/* ユーザーID */
	$first_name = "";								/* 姓 */
	$last_name = "";								/* 名 */
	$full_name = "";								/* 姓 + 名 */
	$tel1 = "";										/* 電話番号1 */
	$tel2 = "";										/* 電話番号2 */
	$tel3 = "";										/* 電話番号3 */
	$tel_number = "";								/* 電話番号1 + 電話番号2 + 電話番号3 */
	$zip_code = "";									/* 郵便番号 */
	$w_addr1 = "";									/* 作業用住所1 */
	$w_addr2 = "";									/* 作業用住所2 */
	$w_addr3 = "";									/* 作業用住所3 */
	$addr1 = "";									/* DB登録用 住所1 + 住所2 */
	$addr2 = "";									/* DB登録用 住所3 */
	$tbl_msg = "";									/* ユーザー情報用テーブルメッセージ */
	$err = array();									/* エラーメッセージ用配列 */
	$columns = array();								/* カラム配列 */
	$cfm_addr = array();							/* 確定住所配列 */
	$db_exec = false;								/* DB実行可能フラグ */
	$db_result = false;								/* DB実行結果フラグ */
	$addr_exist_chk = false;						/* アドレス存在チェック */
	$_SESSION['cfm_addr'] = "";						/* 配送先確定セッション */

	/* ----テスト用 変更予定----
	--------ここから-------- */
	$datebase_dsn = "mysql:host=localhost;dbname=web1903";
	$datebase_user = "root";
	$datebase_password = "";
	/* ----テスト用 変更予定----
	--------ここまで-------- */
	$db = new Database($datebase_dsn, $datebase_user, $datebase_password);

	/* デバッグ用 */
	$_SESSION[SK_USER_ID] = "test1010";
	/* デバッグ用 */

	if( isset($_SESSION[SK_USER_ID]) ){
		$user_id = $_SESSION[SK_USER_ID];
	}

	if( isPost() ){
		/* どのボタンが押されたのか、何番の住所が押されたのか判定 */
		if( isset($_POST['use_addr']) ){
			/* 住所を住所確定セッションへ格納してリダイレクト */
			$_SESSION['cfm_addr'] = $_SESSION['addrs'][$_POST['use_addr_no']];

			/* テスト用手動リンク */
			var_dump($_SESSION['cfm_addr']);
			echo '<a href="./cart_check.php">リンク</a>';

			/* テストが終わったらコメント解除 */
			// Header("Location: cart_check.php");
			// exit;
		}

		if( isset($_POST['edit_addr']) ){
			/* 住所を住所確定セッションへ格納してリダイレクト */
			$_SESSION['cfm_addr'] = $_SESSION['addrs'][$_POST['edit_addr_no']];

			/* テスト用手動リンク */
			var_dump($_SESSION['cfm_addr']);
			echo '<a href="./edit_addr.php">リンク</a>';

			/* テストが終わったらコメント解除 */
			// Header("Location: edit_addr.php");
			// exit;
		}

		if( isset($_POST['del_addr']) ){
			/* 住所を住所確定変数へ格納してリダイレクト */
			$cfm_addr = $_SESSION['addrs'][$_POST['del_addr_no']];

			/* 削除フラグ建て用SQL */
			$sql = 'UPDATE k2g2_addr SET addr_status = ? WHERE addr_fullname = ? AND addr_zip = ? AND addr_1 = ? AND addr_2 = ? AND addr_tel = ?';
			$columns = array(1, $cfm_addr['addr_fullname'], $cfm_addr['addr_zip'], $cfm_addr['addr_1'], $cfm_addr['addr_2'], $cfm_addr['addr_tel']);
			
			/* DB実行結果を格納 */
			if( $db->exec($sql, $columns) ) $db_result = true;
		}

		/* 住所追加の場合 */
		if( isset($_POST['ud']) ){
			echo "更新だよ";

			/* 姓 入力値チェック */
			$first_name = h($_POST['first_name']);
			if( isBlank($first_name) ){
				$err[] = "姓が入力されていません。";
			}

			/* 名 入力値チェック */
			$last_name = h($_POST['last_name']);
			if( isBlank($last_name) ){
				$err[] = "名が入力されていません。";
			}

			/* フルネーム生成 */
			if( !isBlank($first_name) && !isBlank($last_name) ){
				$full_name = $first_name.$last_name;
			}

			/* 電話番号 入力値チェック */
			$tel1 = h($_POST['tel1']);
			$tel2 = h($_POST['tel2']);
			$tel3 = h($_POST['tel3']);
			if( isBlank($tel1) || isBlank($tel2) || isBlank($tel3) ){
				$err[] = "電話番号が入力されていません。";
			} else if( !preg_match("/[0-9]/", $tel1) || !preg_match("/[0-9]/", $tel2) || !preg_match("/[0-9]/", $tel3) ) {
				$err[] = "電話番号は半角数字で入力してください。";
			} else {
				/* 電話番号 生成+桁数チェック ハイフンなし 10～11桁 */
				$tel_number = $tel1.$tel2.$tel3;
				if( !isLength($tel_number, 10, 11) ){
					$err[] = "電話番号の桁数が不正です。<br />正しい電話番号を入力して下さい。";
				}
			}

			/* 郵便番号 入力値チェック */
			$zip_code = h($_POST['zip_code']);
			if( isBlank($zip_code) ){
				$err[] = "郵便番号が入力されていません。";
			} else if( !preg_match("/[0-9]/", $zip_code) ){
				$err[] = "郵便番号は半角数字で入力してください。";
			}
			
			/* 住所 成形 */
			$w_addr1 = h($_POST['addr1']);
			$w_addr2 = h($_POST['addr2']);
			$w_addr3 = h($_POST['addr3']);
			$addr1 = $w_addr1.$w_addr2;
			$addr2 = $w_addr3;

			/* エラーがなければDB登録 */
			if( !count($err) ) $db_exec = true;

			if( $db_exec ){
				/* 配送先情報を登録 */
				$sql = 'INSERT INTO k2g2_addr';
				$sql .= ' (addr_user_id, addr_fullname, addr_zip, addr_1, addr_2, addr_tel)';
				$sql .= ' value (?, ?, ?, ?, ?, ?)';
				$columns = array($user_id, $full_name, $zip_code, $addr1, $addr2, $tel_number);

				/* DB実行結果を格納 */
				if( $db->exec($sql, $columns) ) $db_result = true;
				echo $db_result;
			}
		}
	} else {
		/* DBから配送先情報を検索する */
		$sql = 'SELECT addr_fullname, addr_zip, addr_1, addr_2, addr_tel FROM k2g2_addr WHERE addr_user_id = ? AND addr_status = 0';
		$columns = array($user_id);

		/* DB検索結果がemptyでなければ、配送先セッションへ結果を格納、アドレス存在チェックをtrueにする */
		if( !empty($db->query($sql, $columns)) ) $_SESSION['addrs'] = $db->query($sql, $columns); $addr_exist_chk = true;

		/* アドレス存在チェックがtrueならforeachを通す */
		if( $addr_exist_chk ){
			/* foreachで分割 */
			/* 要素にspan割り当ててもいいかもね */
			foreach( $_SESSION['addrs'] as $key => $addr ){
				$tbl_msg .= '<table border="1"><form action="'.self().'" method="post">';
				$tbl_msg .= '<td><span>'.$addr['addr_fullname'].'</span><br /><span>'.$addr['addr_zip'].'</span><br /><span>'.$addr['addr_1'].'</span><br /><span>'.$addr['addr_2'].'</span><br /><span>'.$addr['addr_tel'].'</span><br />';
				$tbl_msg .= '<input type="hidden" name="use_addr_no" value="'.$key.'" /><input type="submit" name="use_addr" value="この住所を使う" /><br />';
				$tbl_msg .= '<input type="hidden" name="edit_addr_no" value="'.$key.'" /><input type="submit" name="edit_addr" value="編集" />&nbsp;&nbsp;&nbsp';
				$tbl_msg .= '<input type="hidden" name="del_addr_no" value="'.$key.'" /><input type="submit" name="del_addr" value="削除" /></td></form></table>';
			}
		}
	}
?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width">
	</head>
	<body>
		<header>
			<h1>白・黒 インテリア</h1>
		</header>
		<div class="existing_address">
			<?= $tbl_msg; ?>
		</div>
		<div class="address_change">
		<form action="<?= self(); ?>" method="post">
				<p>
					<label for="sei">姓</label>
					<input type="text" name="first_name" value="<?= $first_name; ?>" size="10">
					<label for="mei">名</label>
					<input type="text" name="last_name" value="<?= $last_name; ?>" size="10"><br>
					<label for="zip_code">郵便番号</label><br>
					<input type="text" name="zip_code" value="<?= $zip_code; ?>" size="5" maxlength='7' placeholder="1234567" onKeyUp="AjaxZip3.zip2addr(this,'','addr1','addr1');">ハイフンは不要です。<br />
					<input type="text" name="addr1" size="30" value="<?= $w_addr1; ?>" placeholder="大阪府和泉市テクノステージ" ><br />
					<input type="text" name="addr2" size="30" value="<?= $w_addr2; ?>" placeholder="３－３－２" ><br />
					<input type="text" name="addr3" size="30" value="<?= $w_addr3; ?>" placeholder="南大阪技専校３F" ><br />
					<label for="tel_number">電話番号</label><br>
					<input type="text" name="tel1" value="<?= $tel1; ?>" size="5" maxlength="4" placeholder="090">
					-
					<input type="text" name="tel2" value="<?= $tel2; ?>" size="5" maxlength="4" placeholder="1234">
					-
					<input type="text" name="tel3" value="<?= $tel3; ?>" size="5" maxlength="4" placeholder="5678"><br>
					<input type="submit" name="ud" value="更新">
				</p>
				</form>
		</div>
		<button type="button" onclick="location.href='index.php'">トップへ</button>
		<footer>
		</footer>
		</body>
</html>

