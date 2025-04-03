<?php
require_once '../config/database.php';
include 'includes/admin-header.php';
?>

<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="flex justify-between items-center p-6 border-b">
        <h1 class="text-xl font-semibold text-gray-800">Thiết lập Cronjob</h1>
    </div>
    
    <div class="p-6">
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 mb-6">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">Hướng dẫn thiết lập Cronjob</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p>Cronjob được sử dụng để tự động kiểm tra và cập nhật trạng thái của các license đã hết hạn. Bạn cần thiết lập cronjob trên máy chủ của mình để chạy API này hàng ngày.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="space-y-6">
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">1. API Endpoint</h3>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <p class="text-sm font-mono break-all"><?php echo "https://" . $_SERVER['HTTP_HOST'] . "/api/cronjob.php"; ?></p>
                </div>
            </div>
            
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">2. API Key</h3>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <p class="text-sm">API key được sử dụng để xác thực yêu cầu. Bạn cần thay đổi API key trong file <code>api/cronjob.php</code>.</p>
                    <p class="text-sm mt-2">Mặc định: <code class="font-mono">YOUR_SECRET_API_KEY</code></p>
                </div>
            </div>
            
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">3. Thiết lập Cronjob</h3>
                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <p class="text-sm mb-2">Sử dụng lệnh cURL để gọi API:</p>
                    <pre class="text-sm font-mono bg-gray-100 p-2 rounded overflow-x-auto">curl -X POST -H "X-API-Key: YOUR_SECRET_API_KEY" <?php echo "https://" . $_SERVER['HTTP_HOST'] . "/api/cronjob.php"; ?></pre>
                    
                    <p class="text-sm mt-4 mb-2">Thiết lập cronjob để chạy hàng ngày (ví dụ: chạy lúc 00:00 mỗi ngày):</p>
                    <pre class="text-sm font-mono bg-gray-100 p-2 rounded overflow-x-auto">0 0 * * * curl -X POST -H "X-API-Key: YOUR_SECRET_API_KEY" <?php echo "https://" . $_SERVER['HTTP_HOST'] . "/api/cronjob.php"; ?> > /dev/null 2>&1</pre>
                </div>
            </div>
            
            <div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">4. Kiểm tra thủ công</h3>
                <p class="text-sm mb-4">Bạn có thể kiểm tra thủ công bằng cách sử dụng nút bên dưới:</p>
                
                <button id="check-manually" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center transition-colors duration-200">
                    <i class="fas fa-sync-alt mr-2"></i> Kiểm tra và cập nhật license hết hạn
                </button>
                
                <div id="result" class="mt-4 hidden">
                    <h4 class="text-md font-medium text-gray-800 mb-2">Kết quả:</h4>
                    <pre id="result-content" class="text-sm font-mono bg-gray-100 p-3 rounded overflow-x-auto"></pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('check-manually').addEventListener('click', async function() {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Đang xử lý...';
        
        try {
            const response = await fetch('<?php echo "/api/cronjob.php"; ?>', {
                method: 'POST',
                headers: {
                    'X-API-Key': 'YOUR_SECRET_API_KEY'
                }
            });
            
            const data = await response.json();
            
            document.getElementById('result').classList.remove('hidden');
            document.getElementById('result-content').textContent = JSON.stringify(data, null, 2);
            
            if (data.success) {
                document.getElementById('result-content').classList.add('text-green-600');
            } else {
                document.getElementById('result-content').classList.add('text-red-600');
            }
        } catch (error) {
            document.getElementById('result').classList.remove('hidden');
            document.getElementById('result-content').textContent = 'Error: ' + error.message;
            document.getElementById('result-content').classList.add('text-red-600');
        }
        
        this.disabled = false;
        this.innerHTML = '<i class="fas fa-sync-alt mr-2"></i> Kiểm tra và cập nhật license hết hạn';
    });
</script>

<?php include 'includes/admin-footer.php'; ?>