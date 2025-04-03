    </main>
    
    <!-- Footer -->
    <footer class="bg-gray-900 border-t border-gray-800 mt-12">
        <div class="container mx-auto px-4 py-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <a href="index.php" class="inline-block mb-4">
                        <img src="assets/images/logo.png" alt="Aurae Software" class="h-10">
                    </a>
                    <p class="text-gray-400 mb-4">
                        Aurae Software cung cấp các giải pháp phần mềm chuyên nghiệp, đáp ứng nhu cầu của doanh nghiệp và cá nhân.
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-cyan-500 transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-cyan-500 transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-cyan-500 transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-cyan-500 transition-colors">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h3 class="text-white text-lg font-semibold mb-4">Sản phẩm</h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="products.php" class="text-gray-400 hover:text-cyan-500 transition-colors">
                                Tất cả sản phẩm
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-400 hover:text-cyan-500 transition-colors">
                                Phần mềm quản lý
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-400 hover:text-cyan-500 transition-colors">
                                Công cụ phát triển
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-400 hover:text-cyan-500 transition-colors">
                                Giải pháp bảo mật
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-white text-lg font-semibold mb-4">Hỗ trợ</h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="contact.php" class="text-gray-400 hover:text-cyan-500 transition-colors">
                                Liên hệ
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-400 hover:text-cyan-500 transition-colors">
                                FAQ
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-400 hover:text-cyan-500 transition-colors">
                                Hướng dẫn sử dụng
                            </a>
                        </li>
                        <li>
                            <a href="#" class="text-gray-400 hover:text-cyan-500 transition-colors">
                                Chính sách bảo hành
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-white text-lg font-semibold mb-4">Liên hệ</h3>
                    <ul class="space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt text-cyan-500 mt-1 mr-3"></i>
                            <span class="text-gray-400">123 Đường ABC, Quận XYZ, TP. Hồ Chí Minh</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone-alt text-cyan-500 mr-3"></i>
                            <span class="text-gray-400">+84 123 456 789</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope text-cyan-500 mr-3"></i>
                            <span class="text-gray-400">info@auraesoft.com</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm mb-4 md:mb-0">
                    &copy; <?php echo date('Y'); ?> Aurae Software. Tất cả quyền được bảo lưu.
                </p>
                <div class="flex space-x-6">
                    <a href="#" class="text-gray-400 hover:text-cyan-500 transition-colors text-sm">
                        Điều khoản sử dụng
                    </a>
                    <a href="#" class="text-gray-400 hover:text-cyan-500 transition-colors text-sm">
                        Chính sách bảo mật
                    </a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile menu
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const closeMobileMenuButton = document.getElementById('close-mobile-menu');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (mobileMenuButton && closeMobileMenuButton && mobileMenu) {
            mobileMenuButton.addEventListener('click', function() {
                mobileMenu.classList.remove('translate-x-full');
            });
            
            closeMobileMenuButton.addEventListener('click', function() {
                mobileMenu.classList.add('translate-x-full');
            });
        }
        
        // Auto-hide notifications after 5 seconds
        const notifications = document.querySelectorAll('.bg-red-900, .bg-green-900');
        if (notifications.length > 0) {
            setTimeout(function() {
                notifications.forEach(function(notification) {
                    notification.style.opacity = '0';
                    notification.style.transition = 'opacity 0.5s ease-in-out';
                    setTimeout(function() {
                        notification.style.display = 'none';
                    }, 500);
                });
            }, 5000);
        }
    });
    </script>
</body>
</html>