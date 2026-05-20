<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam Machine - Mailer</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; background: #f0f2f5; margin: 0; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.5rem; font-weight: bold; }
        input, textarea { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; padding: 0.75rem; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        button:hover { background: #0056b3; }
        .status { padding: 1rem; margin-bottom: 1rem; border-radius: 4px; text-align: center; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Send Email</h2>
        <?php if (isset($_GET['status'])): ?>
            <div class="status <?php echo $_GET['status'] === 'success' ? 'success' : 'error'; ?>">
                <?php echo $_GET['status'] === 'success' ? 'Email sent!' : 'Failed to send.'; ?>
            </div>
        <?php endif; ?>
        <form action="send.php" method="POST">
            <div class="form-group">
                <label>Recipient Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Message</label>
                <textarea name="message" rows="4" required></textarea>
            </div>
            <button type="submit">Send</button>
        </form>
    </div>
</body>
</html>
