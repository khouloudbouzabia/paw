// js/main.js
$(document).ready(function() {


    // Menu mobile pour les petits écrans
    $('.menu-toggle').click(function() {
        $('.navbar').toggleClass('active');
        $(this).find('i').toggleClass('fa-bars fa-times');
    });

    // Fermer le menu lorsqu'on clique sur un lien
    $('.nav-links a').click(function() {
        if ($(window).width() <= 992) {
            $('.navbar').removeClass('active');
            $('.menu-toggle i').removeClass('fa-times').addClass('fa-bars');
        }
    });


    // Changer la barre de navigation lors du scroll
    $(window).scroll(function() {
        const navbar = $('.navbar');
        if ($(window).scrollTop() > 50) {
            navbar.addClass('scrolled');
        } else {
            navbar.removeClass('scrolled');
        }
    });


    // Activer le lien de navigation actuel
    function setActiveNavLink() {
        const currentPage = window.location.pathname.split('/').pop() || 'index.html';
        $('.nav-links a').removeClass('active');
        $('.nav-links a[href="' + currentPage + '"]').addClass('active');
    }
    setActiveNavLink();


    // Chargement dynamique du contenu
    function loadPageContent(page) {
        // Afficher le loader
        showLoading();
        
        // Charger le contenu
        $.ajax({
            url: page,
            type: 'GET',
            success: function(response) {
                // Analyser le HTML et extraire le contenu
                const $content = $(response).find('.main-content').html();
                if ($content) {
                    $('.main-content').html($content);
                    // Mettre à jour les liens actifs
                    setActiveNavLink();
                    // Exécuter les fonctions spécifiques à la page
                    initializePageFunctions();
                }
                // Cacher le loader
                hideLoading();
                
                // Remonter en haut de la page
                $('html, body').animate({ scrollTop: 0 }, 300);
            },
            error: function() {
                hideLoading();
                alert('Une erreur est survenue lors du chargement de la page. Veuillez réessayer.');
            }
        });
    }

    // Empêcher le rechargement complet pour les liens internes
    $(document).on('click', 'a[data-ajax="true"]', function(e) {
        e.preventDefault();
        const page = $(this).attr('href');
        loadPageContent(page);
        
        // Mettre à jour l’URL dans le navigateur sans recharger
        if (history.pushState) {
            history.pushState(null, null, page);
        }
    });


    // Fonctions utilitaires
    function showLoading() {
        $('body').append('<div class="loading-overlay"><div class="spinner"></div></div>');
    }

    function hideLoading() {
        $('.loading-overlay').fadeOut(300, function() {
            $(this).remove();
        });
    }

    function formatDate(date) {
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            timeZone: 'Asia/Riyadh'
        };
        return new Date(date).toLocaleDateString('ar-SA', options);
    }

    function formatTime(time) {
        const [hours, minutes] = time.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'م' : 'ص';
        const formattedHour = hour % 12 || 12;
        return `${formattedHour}:${minutes} ${ampm}`;
    }

 
    // Validation des formulaires
    $.fn.validateForm = function() {
        let isValid = true;
        $(this).find('input[required], select[required], textarea[required]').each(function() {
            if (!$(this).val().trim()) {
                isValid = false;
                $(this).addClass('error');
                $(this).attr('placeholder', 'Ce champ est obligatoire');
            } else {
                $(this).removeClass('error');
            }
        });
        return isValid;
    };


    // Affichage des notifications
    function showNotification(message, type = 'info') {
        const notification = $('<div class="notification ' + type + '">' + message + '</div>');
        $('body').append(notification);
        
        // Ajouter le bouton de fermeture
        const closeBtn = $('<span class="close-notification">&times;</span>');
        notification.append(closeBtn);
        
        // Afficher la notification
        notification.fadeIn(300);
        
        // Masquer automatiquement après 5 secondes
        setTimeout(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Fermeture manuelle
        closeBtn.click(function() {
            notification.fadeOut(300, function() {
                $(this).remove();
            });
        });
    }


    // Formatage dynamique
    function initializePageFunctions() {
        // Formatage des dates
        $('.date-display').each(function() {
            const date = $(this).data('date');
            if (date) {
                $(this).text(formatDate(date));
            }
        });

        // Formatage des heures
        $('.time-display').each(function() {
            const time = $(this).data('time');
            if (time) {
                $(this).text(formatTime(time));
            }
        });

        // Formatage des prix
        $('.price-display').each(function() {
            const price = parseFloat($(this).data('price'));
            if (!isNaN(price)) {
                $(this).text(price.toFixed(2) + 'DA');
            }
        });

        // Formatage des numéros de téléphone
        $('.phone-link').each(function() {
            const phone = $(this).text().trim();
            const cleanPhone = phone.replace(/\D/g, '');
            if (cleanPhone) {
                $(this).attr('href', 'tel:+966' + cleanPhone);
            }
        });

        // Formatage des emails
        $('.email-link').each(function() {
            const email = $(this).text().trim();
            if (email.includes('@')) {
                $(this).attr('href', 'mailto:' + email);
            }
        });
    }

    // Exécuter le formatage au chargement de la page
    initializePageFunctions();


    // Scroll fluide pour les ancres
    $(document).on('click', 'a[href^="#"]', function(e) {
        e.preventDefault();
        const target = $(this.hash);
        if (target.length) {
            $('html, body').animate({
                scrollTop: target.offset().top - 80
            }, 800);
        }
    });


    // Filtrage des éléments
    $(document).on('input', '.search-filter', function() {
        const searchTerm = $(this).val().toLowerCase();
        const target = $(this).data('target');
        
        $(target).each(function() {
            const text = $(this).text().toLowerCase();
            if (text.includes(searchTerm)) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

  
    // Minuteur et heure actuelle
    function updateDateTime() {
        const now = new Date();
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            timeZone: 'Asia/Riyadh'
        };
        const dateTimeString = now.toLocaleDateString('ar-SA', options);
        $('.current-datetime').text(dateTimeString);
    }

    // Mise à jour de l'heure chaque seconde
    setInterval(updateDateTime, 1000);
    updateDateTime();


    // Gestion de la disponibilité
    $(document).on('click', '.toggle-availability', function() {
        const button = $(this);
        const itemId = button.data('id');
        const currentState = button.data('available') === 'true';
        const newState = !currentState;
        
        $.ajax({
            url: 'php/toggle_availability.php',
            type: 'POST',
            data: {
                id: itemId,
                available: newState
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    button.data('available', newState);
                    button.toggleClass('available unavailable');
                    button.find('i').toggleClass('fa-check fa-times');
                    button.find('span').text(newState ? 'Disponible' : 'Indisponible');
                    
                    showNotification('La disponibilité a été mise à jour avec succès', 'success');
                } else {
                    showNotification('Erreur lors de la mise à jour de la disponibilité', 'error');
                }
            },
            error: function() {
                showNotification('Erreur de connexion au serveur', 'error');
            }
        });
    });

 
    // Requêtes AJAX génériques
    window.ajaxRequest = function(url, data, successCallback, errorCallback) {
        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (successCallback) successCallback(response);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                if (errorCallback) {
                    errorCallback(xhr, status, error);
                } else {
                    showNotification('Erreur de connexion au serveur', 'error');
                }
            }
        });
    };


    // Gestion de session et vérification
    function checkSession() {
        $.ajax({
            url: 'php/check_session.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (!response.loggedIn && window.location.pathname.includes('admin/')) {
                    window.location.href = 'admin/login.php';
                }
            }
        });
    }

    // Vérification de session toutes les 5 minutes
    setInterval(checkSession, 300000);
    
    // Vérification au chargement de la page
    if (window.location.pathname.includes('admin/')) {
        checkSession();
    }


    // Graphiques et données
    function loadChartData() {
        if ($('#reservationsChart').length) {
            $.ajax({
                url: 'php/get_chart_data.php',
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        renderReservationsChart(data.reservations);
                    }
                }
            });
        }
    }

    function renderReservationsChart(data) {
        // Cette fonction sera remplie après inclusion d'une bibliothèque de graphiques (ex: Chart.js)
        console.log('Données du graphique chargées:', data);
    }


    // Lazy loading des images
    function lazyLoadImages() {
        const images = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });

        images.forEach(img => imageObserver.observe(img));
    }

    // Exécuter le lazy load
    lazyLoadImages();


    // Contrôle des vidéos
    $(document).on('click', '.video-control', function() {
        const video = $(this).closest('.video-container').find('video')[0];
        if (video.paused) {
            video.play();
            $(this).html('<i class="fas fa-pause"></i>');
        } else {
            video.pause();
            $(this).html('<i class="fas fa-play"></i>');
        }
    });


    // Préchargement des pages
    function preloadPages() {
        const pages = ['reservation.html', 'menu.html', 'about.html', 'contact.html'];
        pages.forEach(page => {
            $('<link>', {
                rel: 'prefetch',
                href: page
            }).appendTo('head');
        });
    }

    // Exécuter le préchargement
    if (window.requestIdleCallback) {
        window.requestIdleCallback(preloadPages);
    } else {
        setTimeout(preloadPages, 1000);
    }


    // Gestion des erreurs générales
    window.onerror = function(message, source, lineno, colno, error) {
        console.error('JavaScript Error:', {
            message: message,
            source: source,
            line: lineno,
            column: colno,
            error: error
        });
        
        // Envoyer le rapport d'erreur au serveur (optionnel)
        if (typeof error !== 'undefined') {
            $.ajax({
                url: 'php/log_error.php',
                type: 'POST',
                data: {
                    message: message,
                    source: source,
                    line: lineno,
                    column: colno,
                    error: error.toString(),
                    url: window.location.href
                }
            });
        }
        
        // Afficher un message utilisateur amical
        showNotification('Une erreur inattendue est survenue. Veuillez rafraîchir la page et réessayer.', 'error');
        
        return true; // Empêcher le message d'erreur par défaut
    };


    // Gestion de la connexion Internet
    window.addEventListener('online', function() {
        showNotification('Connexion Internet rétablie', 'success');
        $('.offline-indicator').fadeOut();
    });

    window.addEventListener('offline', function() {
        showNotification('Connexion Internet perdue. Les données seront sauvegardées localement jusqu’au retour de la connexion.', 'warning');
        $('.offline-indicator').fadeIn();
    });

    // Vérifier l'état de connexion au chargement
    if (!navigator.onLine) {
        $('.offline-indicator').show();
    }
});
