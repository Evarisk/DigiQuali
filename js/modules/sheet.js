/**
 * Initialise l'objet "sheet" ainsi que la méthode "init" obligatoire pour la bibliothèque EoxiaJS.
 *
 * @since   1.3.0
 * @version 1.3.0
 */
window.digiquali.sheet = {};

/**
 * La méthode appelée automatiquement par la bibliothèque EoxiaJS.
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.digiquali.sheet.init = function() {
	window.digiquali.sheet.event();
};

/**
 * La méthode contenant tous les événements pour la fiche modèle.
 *
 * @since   1.3.0
 * @version 1.3.0
 *
 * @return {void}
 */
window.digiquali.sheet.event = function() {

  $( document ).on( 'click', '.toggle-group-in-tree', window.digiquali.sheet.toggleGroupInTree );
//  $( document ).on( 'click', '.group-item', window.digiquali.sheet.showQuestionGroupCard );
//  $( document ).on( 'click', '.question-item', window.digiquali.sheet.showQuestionCard );
//  $( document ).on( 'click', '.sheet-header', window.digiquali.sheet.showSheet );
  $( document ).on( 'click', '#addQuestionButton, #addGroupButton', window.digiquali.sheet.buttonActions );

  window.digiquali.sheet.hookAjaxForms();
  window.digiquali.sheet.hookAjaxLinks();
};

window.digiquali.sheet.buttonActions = function() {
    const addQuestionRow = document.getElementById("addQuestionRow");
    const addGroupRow = document.getElementById("addGroupRow");

    if ($(this).attr('id') === 'addQuestionButton') { 
      addQuestionRow.classList.remove("hidden");
      addGroupRow.classList.add("hidden");
    } else {
      addGroupRow.classList.remove("hidden");
      addQuestionRow.classList.add("hidden");
    }
}

function toggleGroup(groupId) {
  const groupQuestions = $(`.group-question-${groupId}`);
  const toggleIcon = document.querySelector(`#group-${groupId} .toggle-icon`);


  groupQuestions.each(function () {
    const question = $(this);
    question.toggleClass('hidden');
    const isHidden = question.hasClass('hidden');
    toggleIcon.textContent = isHidden ? '+' : '-';
  });
}

window.digiquali.sheet.closeAllGroups = function () {
  const groupQuestions = document.querySelectorAll('.group-question');

  groupQuestions.forEach(question => {
    question.classList.add('hidden');
  });

  const toggleIcons = document.querySelectorAll('.toggle-icon');

  toggleIcons.forEach(icon => {
    icon.textContent = '+';
  });
}

window.digiquali.sheet.toggleGroupInTree = function () {
  let subQuestions = $(this).closest('.group-item').next()[0];
  $(this).toggleClass('fa-chevron-up fa-chevron-down');

  if (subQuestions && subQuestions.classList.contains("sub-questions")) {
    subQuestions.classList.toggle("collapsed");
  }
}

window.digiquali.sheet.showQuestionGroupCard = function () {
  window.digiquali.sheet.greyOut();
  const id = $(this).data("id");
  const token = window.saturne.toolbox.getToken()
  const url = $('#questionGroupCardUrl').val() + '?id=' + id;
  $.ajax({
    url: url + '&token=' + token,
    type: "POST",
    processData: false,
    contentType: false,
    success: function( resp ) {
      $('#cardContent').html($(resp).find('#cardContent').html());
      $(`.group-item[data-id=${id}]`).addClass('selected');

      window.digiquali.sheet.hookAjaxForms();
      window.digiquali.sheet.hookAjaxLinks();
    }
    },
  );
}

window.digiquali.sheet.showQuestionCard = function () {
  window.digiquali.sheet.greyOut();
  const id = $(this).data("id");
  const groupId = $(this).data("group-id")
  const token = window.saturne.toolbox.getToken()
  const url = $('#questionCardUrl').val() + '?id=' + id;
  $.ajax({
      url: url + '&token=' + token,
      type: "POST",
      processData: false,
      contentType: false,
      success: function( resp ) {
        $('#cardContent').html($(resp).find('#cardContent').html());
        $(`.question-item[data-id='${id}'][data-group-id='${groupId}']`).addClass('selected');

        window.digiquali.sheet.hookAjaxForms();
        window.digiquali.sheet.hookAjaxLinks();
      }
    },
  );
}

window.digiquali.sheet.showSheet = function () {
  window.digiquali.sheet.greyOut();
  const id = $(this).data("id");
  const token = window.saturne.toolbox.getToken();
  const url = $('#sheetCardUrl').val() + '?id=' + id;

  $.ajax({
      url: url + '&token=' + token,
      type: "POST",
      processData: false,
      contentType: false,
      success: function(resp) {
        let newContent = $(resp).find('#cardContent').removeClass('margin-for-tree');
        $('#cardContent').children().remove();
        $('#cardContent').append(newContent.children());
        window.digiquali.sheet.hookAjaxForms();
        window.digiquali.sheet.hookAjaxLinks();
        $(`.sheet-header[data-id=${id}]`).addClass('selected');
        window.digiquali.sheet.hookAjaxForms();
        window.digiquali.sheet.hookAjaxLinks();
      }
    }
  );
};

window.digiquali.sheet.hookAjaxForms = function() {
  console.log("🔄 hookAjaxForms activé");

  let forms = $("#cardContent form");
  console.log(`📄 ${forms.length} formulaire(s) détecté(s)`);
//show form values
  console.log(forms[0].elements);

  $(document).off('submit', '#cardContent form');

  $(document).on("click", "button, input[type='submit']", function () {
    let form = $(this).closest("form");
    if (form.length === 0) {
      form = $(this).closest('tr').find('form');
    }
    form.submit();
  });
};

window.digiquali.sheet.hookAjaxLinks = function() {
  $('#cardContent a').off('click').on('click', function(event) {
    let href = $(this).attr('href');
    console.log('la ca va')

    // Vérifie si le lien a une action spécifique
    if (!href || href.startsWith('javascript:') || href.includes('#')) {
      return; // Ne rien faire si c'est un lien interne
    }

    event.preventDefault(); // Empêche la redirection

    $.ajax({
      url: href,
      type: "GET",
      success: function(response) {
        let newContent = $(response).find('#cardContent').removeClass('margin-for-tree');
        $('#cardContent').html(newContent);

        // Réattache les événements
        window.digiquali.sheet.hookAjaxForms();
        window.digiquali.sheet.hookAjaxLinks();
      },
      error: function(xhr, status, error) {
        console.error("❌ Erreur AJAX lors du chargement du lien :", error);
      }
    });
  });
};

window.digiquali.sheet.greyOut = function () {
  const questionGroups = document.querySelectorAll('.group-item');
  const questions = document.querySelectorAll('.question-item');
  const sheets = document.querySelectorAll('.sheet-header');

  questionGroups.forEach(group => {
    group.classList.remove('selected');
  });

  questions.forEach(question => {
    question.classList.remove('selected');
  });

  sheets.forEach(sheet => {
    sheet.classList.remove('selected');
  });
}
