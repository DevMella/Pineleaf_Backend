<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PineLeaf</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color:#f9f9f9;">

    <!-- Header -->
    <div style="background-color: rgba(24, 83, 29, 0.5); text-align:center; padding:20px 0;">
        <img src="https://pineleafestates.com/views/index_images/logo.png" alt="PineLeaf Logo" style="width:120px;">
    </div>

    <!-- Main Content -->
    <div style="padding: 20px 30px; max-width: 700px; margin: auto; background-color:#fff;">
        <h2 style="text-align:center; color:#2F5318; font-size:28px;">Your Purchase was Successful</h2>
        
        <p><strong>Hi {{ $firstName }},</strong></p>
        <p style="line-height: 1.6;">Great news! Your land purchase has been confirmed.</p>
        
        <p><strong>Details:</strong></p>
        <p style="line-height: 1.6;">Amount: ₦{{ number_format($transaction->amount, 2) }}</p>
        <p style="line-height: 1.6;">Date: {{ $transaction->created_at->format('l, F j, Y \a\t h:i A') }}</p>

        <p style="line-height: 1.6;">Your wallet balance has been updated accordingly.</p>
        <p style="line-height: 1.6;">Thank you for trusting Pineleaf Estate.</p>

        <br>
        <p><strong>Need help?</strong></p>
        <p style="color:#2F5318;">info@pineleafestate.com</p>

        <br>
        <p>Warm regards,</p>
        <p>The Pineleaf Estate Team</p>
    </div>

    <!-- Footer -->
    <div style="background-color: rgba(24, 83, 29, 0.5); text-align:center; padding:20px 0; margin-top:30px;">
        <p style="color: #fbfcfb; font-size: 16px; margin-bottom: 10px;"><strong>Follow Us</strong></p>
        
        <!-- Social Icons (simple fallback if SVGs are not needed) -->
        <div style="display:flex; justify-content:center; gap:15px;align-items:center">
            <a href="#" style="display:inline-block;">
                <img src="https://upload.wikimedia.org/wikipedia/commons/0/05/Facebook_Logo_%282019%29.png" alt="Facebook" style="width:24px;">
            </a>
            <a href="#" style="display:inline-block;">
                <img src="https://upload.wikimedia.org/wikipedia/commons/a/a5/Instagram_icon.png" alt="Instagram" style="width:24px;">
            </a>
        </div>
    </div>

</body>
</html>
