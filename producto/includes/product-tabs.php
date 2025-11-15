<?php
// Verificar si hay preguntas para mostrar el contador correctamente
$questionsCount = count($questionsData);
$reviewsCount = $reviewsData['stats']['total_reviews'];
?>

<section class="product-tabs-section">
    <div class="tabs-header">
        <button class="tab-btn active" data-tab="description">
            <i class="fas fa-file-alt"></i> Descripción
        </button>
        <button class="tab-btn" data-tab="specs">
            <i class="fas fa-list-ul"></i> Especificaciones
        </button>
        <button class="tab-btn" data-tab="reviews">
            <i class="fas fa-star"></i> Opiniones (<?= $reviewsCount ?>)
        </button>
        <button class="tab-btn" data-tab="questions">
            <i class="fas fa-question-circle"></i> Preguntas (<?= $questionsCount ?>)
        </button>
    </div>
    
    <div class="tabs-content">
        <!-- Descripción -->
        <div id="description" class="tab-pane active">
            <h3>Detalles del producto</h3>
            <p><?= nl2br(htmlspecialchars($product['description'] ?? 'Descripción no disponible')) ?></p>
            
            <?php if (!empty($additionalImages)): ?>
            <div class="description-images">
                <?php foreach ($additionalImages as $image): ?>
                    <img src="<?= BASE_URL ?>/<?= htmlspecialchars($image['image_path']) ?>" alt="<?= htmlspecialchars($image['alt_text']) ?>">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Especificaciones -->
        <div id="specs" class="tab-pane">
            <div class="specs-grid">
                <div class="spec-item">
                    <span class="spec-label">Material:</span>
                    <span class="spec-value"><?= htmlspecialchars($product['material'] ?? 'No especificado') ?></span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Cuidados:</span>
                    <span class="spec-value"><?= htmlspecialchars($product['care_instructions'] ?? 'No especificado') ?></span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Género:</span>
                    <span class="spec-value">
                        <?= 
                            $product['gender'] === 'niño' ? 'Niño' : 
                            ($product['gender'] === 'niña' ? 'Niña' : 
                            ($product['gender'] === 'bebe' ? 'Bebé' : 'Unisex'))
                        ?>
                    </span>
                </div>
                <div class="spec-item">
                    <span class="spec-label">Categoría:</span>
                    <span class="spec-value"><?= htmlspecialchars($product['category_name']) ?></span>
                </div>
                <?php if (!empty($product['collection_name'])): ?>
                <div class="spec-item">
                    <span class="spec-label">Colección:</span>
                    <span class="spec-value"><?= htmlspecialchars($product['collection_name']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Reseñas -->
        <div id="reviews" class="tab-pane">
            <div class="reviews-summary">
                <div class="summary-rating">
                    <div class="average-rating">
                        <?= number_format($reviewsData['stats']['average_rating'] ?: 0, 1) ?>
                        <div class="stars">
                            <?php 
                            $avgRating = $reviewsData['stats']['average_rating'] ?: 0;
                            for ($i = 1; $i <= 5; $i++):
                                $class = $i <= floor($avgRating) ? 'fas fa-star' : 
                                        ($i <= ceil($avgRating) ? 'fas fa-star-half-alt' : 'far fa-star');
                            ?>
                                <i class="<?= $class ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <span><?= $reviewsData['stats']['total_reviews'] ?> opiniones</span>
                    </div>
                </div>
                
                <div class="rating-bars">
                    <?php for ($i = 5; $i >= 1; $i--): 
                        $percent = $reviewsData['stats'][$i.'_star_percent'] ?? 0;
                    ?>
                        <div class="rating-bar">
                            <span class="star-count"><?= $i ?> <i class="fas fa-star"></i></span>
                            <div class="bar-container">
                                <div class="bar" style="width: <?= $percent ?>%"></div>
                            </div>
                            <span class="percent"><?= $percent ?>%</span>
                        </div>
                    <?php endfor; ?>
                </div>
                
            
            </div>
                <?php if ($canReview): ?>
                    <button id="write-review-btn" class="tab-action-btn review-btn">
                        <i class="fas fa-pen"></i> Escribir una opinión
                    </button>
                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <div class="review-note">
                        <i class="fas fa-info-circle"></i> Solo los clientes que han comprado este producto pueden dejar una opinión.
                    </div>
                <?php else: ?>
                    <div class="review-note">
                        <i class="fas fa-info-circle"></i> <a href="<?= BASE_URL ?>/login">Inicia sesión</a> para dejar una opinión (solo para clientes que han comprado este producto).
                    </div>
                <?php endif; ?>
            
            <!-- Formulario de reseña (oculto inicialmente) -->
            <?php if ($canReview): ?>
            <div id="review-form-container" class="review-form-container" style="display: none;">
                <h3>Escribe tu opinión</h3>
                <form id="review-form" method="POST" action="<?= BASE_URL ?>/api/submit_review.php">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    
                    <div class="form-group">
                        <label>Calificación</label>
                        <div class="rating-input">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="far fa-star" data-rating="<?= $i ?>"></i>
                            <?php endfor; ?>
                            <input type="hidden" name="rating" id="rating-value" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="review-title">Título</label>
                        <input type="text" id="review-title" name="title" required 
                               placeholder="Ej: Excelente calidad, muy recomendado">
                    </div>
                    
                    <div class="form-group">
                        <label for="review-comment">Comentario</label>
                        <textarea id="review-comment" name="comment" rows="5" required
                                  placeholder="Comparte tu experiencia con este producto"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Fotos (opcional)</label>
                        <div class="image-upload">
                            <div class="upload-preview"></div>
                            <label class="upload-btn">
                                <i class="fas fa-camera"></i> Subir imágenes
                                <input type="file" name="review_images[]" multiple accept="image/*" style="display: none;">
                            </label>
                            <span class="upload-note">Máx. 5 imágenes (JPEG, PNG)</span>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" id="cancel-review" class="tab-action-btn question-btn">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="tab-action-btn review-btn">
                            <i class="fas fa-paper-plane"></i> Enviar opinión
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
            
            <!-- Lista de reseñas -->
            <div class="reviews-list">
                <?php if (empty($reviewsData['reviews'])): ?>
                    <div class="no-reviews">
                        <i class="fas fa-comment-alt"></i>
                        <p>Este producto aún no tiene opiniones. Sé el primero en opinar.</p>
                        
                    </div>
                <?php else: ?>
                    <?php foreach ($reviewsData['reviews'] as $review): ?>
                        <div class="review-item">
                            <!-- Contenido de la reseña existente -->
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ($reviewsData['stats']['total_reviews'] > 10): ?>
                        <a href="<?= BASE_URL ?>/producto/opiniones/<?= $product['slug'] ?>" class="see-all-reviews">
                            Ver todas las opiniones (<?= $reviewsData['stats']['total_reviews'] ?>)
                        </a>
                    <?php endif; ?>
            </div>
        </div>
        
        <!-- Preguntas y respuestas -->
        <div id="questions" class="tab-pane">
            <div class="questions-header">
                <h3>Preguntas y respuestas</h3>
                <button id="ask-question-btn" class="tab-action-btn question-btn">
                    <i class="fas fa-question-circle"></i> Hacer una pregunta
                </button>
            </div>
            
            <!-- Formulario de pregunta (oculto inicialmente) -->
            <div id="question-form-container" class="question-form-container" style="display: none;">
                <form id="question-form" method="POST" action="<?= BASE_URL ?>/api/submit_question.php">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    
                    <div class="form-group">
                        <label for="question-text">Tu pregunta</label>
                        <textarea id="question-text" name="question" rows="3" required
                                  placeholder="Escribe tu pregunta sobre este producto (mín. 10 caracteres)"></textarea>
                        <small class="form-note">Las preguntas serán respondidas por el vendedor u otros compradores.</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" id="cancel-question" class="tab-action-btn review-btn">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="tab-action-btn question-btn">
                            <i class="fas fa-paper-plane"></i> Enviar pregunta
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Lista de preguntas -->
            <div class="questions-list" data-qa-questions-count="<?= count($questionsData) ?>">
                <?php if (empty($questionsData)): ?>
                    <div class="no-questions">
                        <i class="fas fa-question-circle"></i>
                        <p>No hay preguntas sobre este producto. Sé el primero en preguntar.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach ($questionsData as $question): ?>
                            <div class="question-item">
                                <div class="question-meta">
                                    <div class="user-avatar">
                                        <img src="<?= BASE_URL ?>/<?= htmlspecialchars($question['user_image'] ?? 'images/default-avatar.png') ?>" alt="<?= htmlspecialchars($question['user_name'] ?? 'Usuario') ?>">
                                    </div>
                                    <div class="user-info">
                                        <strong><?= htmlspecialchars($question['user_name'] ?? 'Usuario') ?></strong>
                                        <span class="time"><?= date('d/m/Y H:i', strtotime($question['created_at'])) ?></span>
                                    </div>
                                </div>
                                <div class="question-text">
                                    <p><?= nl2br(htmlspecialchars($question['question'])) ?></p>
                                </div>

                                <?php if (!empty($question['answers'])): ?>
                                    <div class="answers-list">
                                        <?php foreach ($question['answers'] as $answer): ?>
                                            <div class="answer-item">
                                                <div class="answer-meta">
                                                    <strong><?= htmlspecialchars($answer['user_name'] ?? 'Usuario') ?></strong>
                                                    <?php if (!empty($answer['is_seller'])): ?>
                                                        <span class="badge seller">Vendedor</span>
                                                    <?php endif; ?>
                                                    <span class="time"><?= date('d/m/Y H:i', strtotime($answer['created_at'])) ?></span>
                                                </div>
                                                <p><?= nl2br(htmlspecialchars($answer['answer'])) ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="question-actions">
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <button class="answer-btn btn small">Responder</button>
                                        <div class="answer-form-container" style="display:none;">
                                            <form method="POST" class="answer-form" action="<?= BASE_URL ?>/api/submit_answer.php">
                                                <input type="hidden" name="question_id" value="<?= $question['id'] ?>">
                                                <div class="form-group">
                                                    <textarea name="answer" rows="3" class="form-control" required placeholder="Escribe tu respuesta"></textarea>
                                                </div>
                                                <div class="form-actions">
                                                    <button type="button" class="cancel-answer btn small">Cancelar</button>
                                                    <button type="submit" class="btn primary small">Enviar</button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    
                        <?php if (count($questionsData) > 5): ?>
                            <a href="<?= BASE_URL ?>/producto/preguntas/<?= $product['slug'] ?>" class="see-all-questions">
                                Ver todas las preguntas (<?= count($questionsData) ?>)
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <script>
                // Debugging: print questions data to console
                try {
                    const qCount = document.querySelector('.questions-list')?.dataset?.qaQuestionsCount;
                    const qNodes = document.querySelectorAll('.questions-list').length;
                    console.log('questionsData count (server):', qCount, 'queryAll length:', qNodes);
                    console.log('product tabs HTML for #questions:', document.querySelector('#questions')?.innerHTML?.slice(0, 400));
                    console.log('questionsData (server raw):', <?= json_encode($questionsData ?: []) ?>);
                } catch(e) { console.error(e); }
            </script>
        </div>
    </div>
</section>

<!-- JavaScript para las pestañas -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Seleccionar elementos
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    // Función para cambiar pestaña
    function switchTab(tabId) {
        // Actualizar botones
        tabButtons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.tab === tabId) {
                btn.classList.add('active');
            }
        });
        
        // Actualizar contenido
        tabPanes.forEach(pane => {
            pane.classList.remove('active');
            if (pane.id === tabId) {
                pane.classList.add('active');
                // Animación de entrada
                pane.style.animation = 'fadeIn 0.5s ease forwards';
            }
        });
        
        // Actualizar indicador de pestaña activa
        updateActiveTabIndicator();
    }
    
    // Función para actualizar el indicador de pestaña activa
    function updateActiveTabIndicator() {
        const activeTab = document.querySelector('.tab-btn.active');
        const tabsHeader = document.querySelector('.tabs-header');
        
        if (activeTab && tabsHeader) {
            // Calcular posición y ancho del indicador
            const indicatorLeft = activeTab.offsetLeft;
            const indicatorWidth = activeTab.offsetWidth;
            
            // Actualizar variables CSS
            document.documentElement.style.setProperty('--indicator-left', indicatorLeft + 'px');
            document.documentElement.style.setProperty('--indicator-width', indicatorWidth + 'px');
        }
    }
    
    // Event listeners para botones de pestaña
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.dataset.tab;
            switchTab(tabId);
        });
    });
    
    // Inicializar
    updateActiveTabIndicator();
    
    // Rating con estrellas
    const stars = document.querySelectorAll('.rating-input .fa-star');
    stars.forEach(star => {
        star.addEventListener('click', function() {
            const rating = this.dataset.rating;
            document.getElementById('rating-value').value = rating;
            
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.classList.remove('far');
                    s.classList.add('fas', 'active');
                } else {
                    s.classList.remove('fas', 'active');
                    s.classList.add('far');
                }
            });
        });
        
        star.addEventListener('mouseover', function() {
            const rating = this.dataset.rating;
            
            stars.forEach((s, index) => {
                if (index < rating) {
                    s.classList.remove('far');
                    s.classList.add('fas', 'hover');
                } else {
                    s.classList.remove('fas', 'hover');
                    s.classList.add('far');
                }
            });
        });
        
        star.addEventListener('mouseout', function() {
            const currentRating = document.getElementById('rating-value').value;
            
            stars.forEach((s, index) => {
                s.classList.remove('hover');
                
                if (index < currentRating) {
                    s.classList.remove('far');
                    s.classList.add('fas', 'active');
                } else {
                    s.classList.remove('fas', 'active');
                    s.classList.add('far');
                }
            });
        });
    });
    
    // Mostrar/ocultar formularios
    const writeReviewBtn = document.getElementById('write-review-btn');
    const askQuestionBtn = document.getElementById('ask-question-btn');
    
    if (writeReviewBtn) {
        writeReviewBtn.addEventListener('click', function() {
            const formContainer = document.getElementById('review-form-container');
            formContainer.style.display = formContainer.style.display === 'none' ? 'block' : 'none';
            
            if (formContainer.style.display === 'block') {
                formContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    }
    
    if (askQuestionBtn) {
        askQuestionBtn.addEventListener('click', function() {
            const formContainer = document.getElementById('question-form-container');
            formContainer.style.display = formContainer.style.display === 'none' ? 'block' : 'none';
            
            if (formContainer.style.display === 'block') {
                formContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    }
    
    // Botones de cancelar
    const cancelReview = document.getElementById('cancel-review');
    const cancelQuestion = document.getElementById('cancel-question');
    
    if (cancelReview) {
        cancelReview.addEventListener('click', function() {
            document.getElementById('review-form-container').style.display = 'none';
        });
    }
    
    if (cancelQuestion) {
        cancelQuestion.addEventListener('click', function() {
            document.getElementById('question-form-container').style.display = 'none';
        });
    }
    
    // Botones flotantes para móvil
    function setupFloatingButtons() {
        if (window.innerWidth < 768) {
            // Crear botones flotantes si no existen
            if (!document.querySelector('.floating-tab-action')) {
                const floatingActions = document.createElement('div');
                floatingActions.className = 'floating-tab-action';
                
                floatingActions.innerHTML = `
                    <button class="floating-tab-action-btn review-btn" data-tab="reviews">
                        <i class="fas fa-star"></i>
                        <span class="tooltip">Escribir opinión</span>
                    </button>
                    <button class="floating-tab-action-btn question-btn" data-tab="questions">
                        <i class="fas fa-question"></i>
                        <span class="tooltip">Hacer pregunta</span>
                    </button>
                `;
                
                document.body.appendChild(floatingActions);
                
                // Event listeners para botones flotantes
                document.querySelectorAll('.floating-tab-action-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const tabId = this.dataset.tab;
                        switchTab(tabId);
                        
                        // Mostrar formulario correspondiente
                        if (tabId === 'reviews') {
                            const reviewForm = document.getElementById('review-form-container');
                            if (reviewForm) reviewForm.style.display = 'block';
                        } else if (tabId === 'questions') {
                            const questionForm = document.getElementById('question-form-container');
                            if (questionForm) questionForm.style.display = 'block';
                        }
                        
                        // Scroll al formulario
                        document.getElementById(tabId)?.scrollIntoView({ behavior: 'smooth' });
                    });
                });
            }
        } else {
            // Eliminar botones flotantes en desktop
            const floatingActions = document.querySelector('.floating-tab-action');
            if (floatingActions) {
                floatingActions.remove();
            }
        }
    }
    
    // Inicializar botones flotantes
    setupFloatingButtons();
    
    // Actualizar en resize
    window.addEventListener('resize', function() {
        updateActiveTabIndicator();
        setupFloatingButtons();
    });
});
</script>