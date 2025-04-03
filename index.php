<?php
require_once 'config/database.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Lấy danh sách sản phẩm
$query = "SELECT * FROM products ORDER BY id ASC";
$stmt = $db->prepare($query);
$stmt->execute();

include 'includes/header.php';
?>

<main class="flex-1">
    <section class="py-20 px-4 md:px-6">
        <div class="container mx-auto max-w-5xl text-center">
            <span class="inline-block mb-4 bg-cyan-500 bg-opacity-10 text-cyan-500 px-3 py-1 rounded-full text-sm font-medium">Mới Ra Mắt</span>
            <h1 class="mb-6 text-4xl font-extrabold tracking-tight sm:text-5xl md:text-6xl">
                Phần Mềm <span class="text-cyan-500">MMO</span>
            </h1>
            <p class="mb-8 text-xl text-gray-400 md:text-2xl">
                Công cụ chuyên nghiệp giúp bạn quản lý và tối ưu hóa hoạt động MMO của mình.
            </p>
        </div>
    </section>

    <section class="py-16 px-4 md:px-6 bg-gray-800 bg-opacity-50">
        <div class="container mx-auto">
            <div class="mb-12 text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl mb-4">Sản Phẩm Của Chúng Tôi</h2>
                <p class="text-gray-400 max-w-2xl mx-auto">
                    Các phần mềm chuyên nghiệp giúp bạn quản lý và tối ưu hóa thu nhập.
                </p>
            </div>

            <div class="grid gap-6 sm:grid-cols-1 lg:grid-cols-2 max-w-5xl mx-auto">
                <?php while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) { 
                    $features = explode(',', $product['features']);
                ?>
                <div class="product-card overflow-hidden rounded-lg border border-gray-800 bg-gray-900 bg-opacity-50 hover:border-gray-700">
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div class="h-10 w-10 rounded-full bg-opacity-20 flex items-center justify-center" style="background-color: <?php echo $product['icon_color']; ?>20; color: <?php echo $product['icon_color']; ?>">
                                <i class="fas fa-<?php echo $product['icon']; ?> text-2xl"></i>
                            </div>
                            <?php if (!empty($product['badge'])) { ?>
                                <span class="badge"><?php echo $product['badge']; ?></span>
                            <?php } ?>
                        </div>
                        <h3 class="mt-4 text-xl font-semibold text-white"><?php echo $product['title']; ?></h3>
                        <p class="text-gray-400"><?php echo $product['description']; ?></p>
                    </div>
                    <div class="p-6 pt-0">
                        <div class="grid grid-cols-2 gap-2">
                            <?php foreach ($features as $feature) { ?>
                                <div class="feature-item">
                                    <div class="feature-dot"></div>
                                    <span class="text-sm text-gray-300"><?php echo trim($feature); ?></span>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-800 bg-gray-900 bg-opacity-80 p-6">
                        <div class="text-xl font-bold <?php echo ($product['price'] >= 1000000) ? 'price-highlight' : ''; ?>">
                            <?php echo number_format($product['price'], 0, ',', '.'); ?> VND
                            <span class="text-sm text-gray-400">/vĩnh viễn</span>
                        </div>
                        <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="bg-cyan-500 hover:bg-cyan-600 text-gray-900 px-3 py-1 rounded-md text-sm font-medium">
                            Chi Tiết
                        </a>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <section class="py-16 px-4 md:px-6">
        <div class="container mx-auto">
            <div class="mb-12 text-center">
                <h2 class="text-3xl font-bold tracking-tight sm:text-4xl mb-4">
                    Tại Sao Chọn Phần Mềm Của Chúng Tôi?
                </h2>
                <p class="text-gray-400 max-w-2xl mx-auto">
                    Chúng tôi cung cấp các giải pháp phần mềm chuyên nghiệp giúp bạn tối ưu hóa hoạt động MMO.
                </p>
            </div>

            <div class="grid gap-8 md:grid-cols-3 max-w-5xl mx-auto">
                <div class="rounded-xl border border-gray-800 bg-gray-900 bg-opacity-50 p-6 text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-cyan-500 bg-opacity-10">
                        <i class="fas fa-shield-alt text-cyan-500 text-xl"></i>
                    </div>
                    <h3 class="mb-2 text-xl font-medium">Bảo Mật Cao</h3>
                    <p class="text-gray-400">
                        Chúng tôi đảm bảo thông tin của bạn luôn được bảo mật và an toàn tuyệt đối.
                    </p>
                </div>
                <div class="rounded-xl border border-gray-800 bg-gray-900 bg-opacity-50 p-6 text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-cyan-500 bg-opacity-10">
                        <i class="fas fa-chart-line text-cyan-500 text-xl"></i>
                    </div>
                    <h3 class="mb-2 text-xl font-medium">Hiệu Suất Cao</h3>
                    <p class="text-gray-400">
                        Phần mềm của chúng tôi được tối ưu hóa để hoạt động nhanh chóng và hiệu quả.
                    </p>
                </div>
                <div class="rounded-xl border border-gray-800 bg-gray-900 bg-opacity-50 p-6 text-center">
                    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-cyan-500 bg-opacity-10">
                        <i class="fas fa-bolt text-cyan-500 text-xl"></i>
                    </div>
                    <h3 class="mb-2 text-xl font-medium">Cập Nhật Liên Tục</h3>
                    <p class="text-gray-400">
                        Chúng tôi liên tục cập nhật phần mềm để đảm bảo tính năng luôn hoạt động tốt nhất.
                    </p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>