<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - Lens Rental</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h3 class="mb-0">Error</h3>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title text-danger"><?php echo $error['type']; ?></h5>
                        <p class="card-text"><?php echo $error['message']; ?></p>
                        
                        <?php if(isset($error['trace']) && ini_get('display_errors')): ?>
                            <div class="mt-4">
                                <h6>Stack Trace:</h6>
                                <pre class="bg-light p-3"><code><?php echo $error['trace']; ?></code></pre>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <a href="<?php echo BASEURL; ?>" class="btn btn-primary">Back to Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 