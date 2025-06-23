<?php
echo 'V1.0.0';

    // Laravel cache'lerini temizle
    echo shell_exec('php artisan cache:clear');
    echo "Application cache has been cleared.\n";

    echo shell_exec('php artisan config:clear');
    echo "Config cache has been cleared.\n";

    echo shell_exec('php artisan route:clear');
    echo "Route cache has been cleared.\n";

    echo shell_exec('php artisan view:clear');
    echo "View cache has been cleared.\n";


    // Tarayıcı cache'i etkisini azaltmak için assetlerin versiyonunu yenileyin
    echo shell_exec('php artisan view:cache');
    echo "View cache has been regenerated.\n";

    // Ayrıca public dizininde yer alan js/css dosyalarının adını değiştirerek de tarayıcı cache sorununu çözebilirsiniz.
    // Örneğin: app.js dosyasını app.js?v=2 gibi çağırabilirsiniz.


