(function () {
  var commentsBlock;

  if (!(commentsBlock = document.getElementById("wac-stub"))) {
    return;
  }

  var postID = commentsBlock.dataset.postid;

  delegateEvent(commentsBlock, "click", ".comment-reply-link", function (e) {
    e.preventDefault();
    replyToComment(e.target);
  });

  delegateEvent(commentsBlock, "submit", "#commentform", function (e) {
    e.preventDefault();
    submitComment(e.target);
  });

  delegateEvent(commentsBlock, "reset", "#commentform", function (e) {
    resetResponder();
  });

  if ("IntersectionObserver" in window) {
    var commentsObserver = new IntersectionObserver(function (changes) {
      changes.forEach((change) => {
        if (change.isIntersecting) {
          loadComments();
          commentsObserver.unobserve(change.target);
        }
      });
    });

    commentsObserver.observe(commentsBlock);
  } else {
    loadComments();
  }

  function replyToComment(target) {
    var link = target;
    var commentID = link.dataset.commentid;
    var commentBlockID = link.dataset.belowelement;
    var respond = document.getElementById("respond");

    document.getElementById("comment_parent").value = commentID;
    document.getElementById(commentBlockID).after(respond);
    document
      .getElementById(commentBlockID)
      .scrollIntoView({ behavior: "smooth" });
  }

  function submitComment(target) {
    var form = target;
    var payload = new FormData(form);
    var placeholder = document.createElement("div");
    placeholder.style = "margin:1em";

    fetch(form.action, {
      method: form.method,
      redirect: "manual",
      body: payload,
    })
      .then(function (response) {
        if (!response.status) {
          return Promise.resolve({
            message: wac_env.pending_msg,
            comment: payload.get("comment"),
          });
        } else if (response.OK) {
          return response.text().then(function (html) {
            var message = new DOMParser()
              .parseFromString(html, "text/html")
              .querySelector(".wp-die-message").innerText;
            return Promise.resolve({ message: message, comment: "" });
          });
        } else {
          return Promise.reject();
        }
      })
      .catch(function (e) {
        return Promise.resolve({ message: wac_env.error_msg, comment: "" });
      })
      .then(function (bundle) {
        placeholder.innerHTML =
          "<p><small>" + bundle.message + "</small></p>" + bundle.comment;
      })
      .finally(function () {
        var responder = document.getElementById("respond");
        responder.before(placeholder);
        document.getElementById("commentform").reset();
      });
  }

  function resetResponder() {
    var respond = document.getElementById("respond");
    document.getElementById("comment_parent").value = "";
    document.getElementById("respond-anchor").before(respond);
  }

  function loadComments() {
    fetch(wac_env.ajaxurl + "?action=wac_load_comments&postID=" + postID)
      .then(function (response) {
        return response.ok && response.text();
      })
      .then(function (comments) {
        commentsBlock.innerHTML = comments;

        var respond = document.getElementById("respond");
        var commentForm = document.getElementById("commentform");
        var formActions = commentForm.querySelector(".form-submit");

        var resetButton = document.createElement("input");
        resetButton.type = "reset";
        resetButton.value = "Cancel";

        var respondAnchor = document.createElement("div");
        respondAnchor.id = "respond-anchor";
        respond.after(respondAnchor);
        formActions.append(resetButton);
      });
  }

  function delegateEvent(delegate, event, target, handler) {
    delegate.addEventListener(event, function (e) {
      e.target.matches(target) && handler(e);
    });
  }
})();
