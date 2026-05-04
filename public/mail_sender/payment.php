<?php 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['EncryptTrans'])) {
	 	$url	=	'https://www.sbiepay.sbi/secure/AggregatorHostedListener';
	
	// Get incoming data
    $EncryptTrans 	= 	$_POST['EncryptTrans'];
	 
	?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Forwarding...</title>
    </head>
    <body>
        <form id="autoForm" action="<?php echo $url; ?>" method="post">
				<input type="hidden" name="EncryptTrans" value="<?php echo $EncryptTrans; ?>">
                <input type="hidden" name="merchIdVal" value="">
            
        </form>
        <script>
            document.getElementById('autoForm').submit();
        </script>
    </body>
    </html>
    <?php
    exit;
}




?>
