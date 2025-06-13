<div id="imageModal" class="modal" role="dialog" aria-modal="true" tabindex="-1" aria-labelledby="modalTitle" style="display:none;">
    <span class="close" id="closeModalBtn" aria-label="Fechar">&times;</span>
    <img class="modal-content" id="modalImage" alt="Imagem ampliada" onclick="event.stopPropagation();">
</div>

<script>
(function() {
    const modal = document.getElementById("imageModal");
    const modalImage = document.getElementById("modalImage");
    const closeBtn = document.getElementById("closeModalBtn");
    let lastFocusedElement = null;

    function escListener(event) {
        if (event.key === "Escape") {
            closeModal();
        }
    }

    window.openModal = function(imageSrc, altText = "Imagem ampliada") {
        lastFocusedElement = document.activeElement;
        modal.style.display = "flex";
        setTimeout(() => modal.classList.add('modal-open'), 10); // trigger fade-in
        modalImage.src = imageSrc;
        modalImage.alt = altText;
        modal.focus();
        document.addEventListener('keydown', escListener);
    };

    window.closeModal = function() {
        modal.classList.remove('modal-open'); // trigger fade-out
        setTimeout(() => {
            modal.style.display = "none";
            modalImage.src = "";
        }, 200); // match CSS transition
        document.removeEventListener('keydown', escListener);
        if (lastFocusedElement) lastFocusedElement.focus();
    };

    // Fechar ao clicar fora da imagem ou no botão fechar
    modal.addEventListener('click', function(e) {
        if (e.target === modal) closeModal();
    });
    closeBtn.addEventListener('click', closeModal);

    // Prevenir scroll da página ao abrir o modal
    modal.addEventListener('wheel', e => e.stopPropagation());
})();
</script>
