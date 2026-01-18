// Mağaza JavaScript dosyası

document.addEventListener('DOMContentLoaded', function() {
    // Sepete ekle butonları
    const addToCartButtons = document.querySelectorAll('.btn-add-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            addToCart(productId);
        });
    });
});

function addToCart(productId) {
    // Sepete ekleme işlemi (ileride geliştirilebilir)
}

