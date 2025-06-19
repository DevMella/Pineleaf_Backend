<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PineLeaf</title>
</head>
<body style="margin: 0; padding: 0; box-sizing: border-box; font-family: Montserrat, Arial, sans-serif;">
  <header style="background-color: #18531D80;">
    <div style="margin: auto; width: 120px;">
      <img src="https://pineleafestates.com/views/index_images/logo.png" alt="" style="width: 100%;">
    </div>
  </header>

  <main style="margin: 0 100px;">
    <h2 style="font-family: Roboto, Helvetica, sans-serif; font-size: 34px; font-weight: 700; text-align: center; margin-top: 20px; color: #2F5318;">Withdrawal Request Received</h2>
    <p><b>Hi {{ $user->fullName }},</b></p>
    <p>We’ve received your withdrawal request.</p>
    <p>Details:</p>
    <p>Amount: ₦{{ number_format($transaction->amount, 2) }}</p>
    <p>Destination: {{ $withdraw->account_name }}</p>
    <p>Date: {{ $withdraw->created_at }}</p><br>
    <p>Please allow up to 24 hours for processing.
      You’ll receive a confirmation once the transaction is completed.</p>
    <p>If you did not authorize this request, contact us immediately.</p><br>
    <p>Need help?</p>
    <p>info@pineleafestate.com</p><br>
    <p>Warm regards,</p>
    <p>The Pineleaf Estate Team</p>
  </main>

  <footer style="background-color: #18531D80; padding: 20px 0; margin-top: 50px;">
    <div style="margin: 10px 0; font-size: 20px;">
      <p><b>Follow Us</b></p>
    </div>
    <div style="display: flex; justify-content: center; gap: 10px; padding-bottom: 20px;">
      <svg xmlns="http://www.w3.org/2000/svg" shape-rendering="geometricPrecision" text-rendering="geometricPrecision" image-rendering="optimizeQuality" fill-rule="evenodd" clip-rule="evenodd" viewBox="0 0 509 509" style="width: 20px;">
        <g fill-rule="nonzero">
          <path fill="#0866FF" d="M509 254.5C509 113.94 395.06 0 254.5 0S0 113.94 0 254.5C0 373.86 82.17 474 193.02 501.51V332.27h-52.48V254.5h52.48v-33.51c0-86.63 39.2-126.78 124.24-126.78 16.13 0 43.95 3.17 55.33 6.33v70.5c-6.01-.63-16.44-.95-29.4-.95-41.73 0-57.86 15.81-57.86 56.91v27.5h83.13l-14.28 77.77h-68.85v174.87C411.35 491.92 509 384.62 509 254.5z"/>
          <path fill="#fff" d="M354.18 332.27l14.28-77.77h-83.13V227c0-41.1 16.13-56.91 57.86-56.91 12.96 0 23.39.32 29.4.95v-70.5c-11.38-3.16-39.2-6.33-55.33-6.33-85.04 0-124.24 40.16-124.24 126.78v33.51h-52.48v77.77h52.48v169.24c19.69 4.88 40.28 7.49 61.48 7.49 10.44 0 20.72-.64 30.83-1.86V332.27h68.85z"/>
        </g>
      </svg>

      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 132.004 132" style="width: 20px;">
        <!-- Instagram gradient icon (truncated for brevity) -->
        <circle cx="66" cy="66" r="66" fill="url(#c)"></circle>
      </svg>
    </div>
    <p style="text-align: center; color: #fbfcfb;">&copy; Pineleaf Estate</p>
  </footer>
</body>
</html>
