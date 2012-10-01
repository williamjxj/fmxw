$(document).ready(function() {
  $('#news-feed').each(function() {
    var $container = $(this);
    $container.empty();

    var fadeHeight = $container.height() / 4;
    for (var yPos = 0; yPos < fadeHeight; yPos += 2) {
      $('<div></div>').css({
        opacity: yPos / fadeHeight,
        top: $container.height() - fadeHeight + yPos
      }).addClass('fade-slice').appendTo($container);
    }

    var $loadingIndicator = $('<img/>')
      .attr({
        'src': './images/loading.gif', 
        'alt': 'Loading. Please wait.'
      })
      .addClass('news-wait')
      .appendTo($container);

    $.get('../test/content.php', function(data) {
										  
	  console.log(data);
	  console.log("\n");
	  
      $loadingIndicator.remove();
      $(data).each(function() {
        var $link = $('<a></a>')
          .attr('href', this.link)
          .text(this.title);
        var $headline = $('<h4></h4>').append($link);

        var $publication = $('<div></div>')
          .addClass('publication-date')
          .text(this.date);
    
        var $summary = $('<div></div>')
          .addClass('summary')
          .html(this.text);
        
        $('<div></div>')
          .addClass('headline')
          .append($headline, $summary)
          .appendTo($container);
      });

      var currentHeadline = 0, oldHeadline = 0;
      var hiddenPosition = $container.height() + 10;

	  for (i=0;i<=2;i++) {
      	//$('div.headline').eq(currentHeadline).css('top', 0);
      	$('div.headline').eq(i).css('top', 150*i);
	  }

	  var headlineCount = $('div.headline').length;
      var pause = false;
      var rotateInProgress = false;

      var headlineRotate = function() {
        if (!rotateInProgress) {
          rotateInProgress = true;
          pause = false;
          currentHeadline = (oldHeadline + 1)
            % headlineCount;

          $('div.headline').eq(oldHeadline).animate(
            {top: -hiddenPosition}, 'slow', function() {
              $(this).css('top', hiddenPosition);
            });
          $('div.headline').eq(currentHeadline).animate(
            {top: 0}, 'slow', function() {
              rotateInProgress = false;
              if (!pause) {
                pause = setTimeout(headlineRotate, 5000);
              }
            });
          $('div.headline').eq(currentHeadline+1).animate(
            {top: 150}, 'slow', function() {
              rotateInProgress = false;
              if (!pause) {
                pause = setTimeout(headlineRotate, 5000);
              }
            });
          $('div.headline').eq(currentHeadline+2).animate(
            {top: 300}, 'slow', function() {
              rotateInProgress = false;
              if (!pause) {
                pause = setTimeout(headlineRotate, 5000);
              }
            });
          oldHeadline = currentHeadline;	  
        }
      };
      if (!pause) {
        pause = setTimeout(headlineRotate, 5000);
      }

	  $container.hover(function() {
        clearTimeout(pause);
        pause = false;
      }, function() {
        if (!pause) {
          pause = setTimeout(headlineRotate, 250);
        }
      });
    }, "json");
  });
});


// Step 10
$(document).ready(function() {
  var spacing = 140;

  function createControl(src) {
    return $('<img/>')
      .attr('src', src)
      .addClass('control')
      .css('opacity', 0.6)
      .css('display', 'none');
  }
      
  var $leftRollover = createControl('./images/left.gif');
  var $rightRollover = createControl('./images/right.gif');
  var $enlargeRollover = createControl('./images/enlarge.gif');
  var $enlargedCover = $('<img/>')
    .addClass('enlarged')
    .hide()
    .appendTo('body');
  var $closeButton = createControl('./images/close.gif')
    .addClass('enlarged-control')
    .appendTo('body');
  var $priceBadge = $('<div/>')
    .addClass('enlarged-price')
    .css('opacity', 0.6)
    .css('display', 'none')
    .appendTo('body');
  var $waitThrobber = $('<img/>')
    .attr('src', './images/wait.gif')
    .addClass('control')
    .css('z-index', 4)
    .hide();
  
  $('#featured-books').css({
    'width': spacing * 3,
    'height': '166px',
    'overflow': 'hidden'
  }).find('.covers a').css({
    'float': 'none',
    'position': 'absolute',
    'left': 1000
  });

  var setUpCovers = function() {
    var $covers = $('#featured-books .covers a');

    $covers.unbind('click mouseenter mouseleave');

    // Left image; scroll right (to view images on left).
    $covers.eq(0)
      .css('left', 0)
      .click(function(event) {
        $covers.eq(0).animate({'left': spacing}, 'fast');
        $covers.eq(1).animate({'left': spacing * 2}, 'fast');
        $covers.eq(2).animate({'left': spacing * 3}, 'fast');
        $covers.eq($covers.length - 1)
          .css('left', -spacing)
          .animate({'left': 0}, 'fast', function() {
            $(this).prependTo('#featured-books .covers');
            setUpCovers();
          });

        event.preventDefault();
      }).hover(function() {
        $leftRollover.appendTo(this).show();
      }, function() {
        $leftRollover.hide();
      });

    // Right image; scroll left (to view images on right).
    $covers.eq(2)
      .css('left', spacing * 2)
      .click(function(event) {
        $covers.eq(0)
          .animate({'left': -spacing}, 'fast', function() {
            $(this).appendTo('#featured-books .covers');
            setUpCovers();
          });
        $covers.eq(1).animate({'left': 0}, 'fast');
        $covers.eq(2).animate({'left': spacing}, 'fast');
        $covers.eq(3)
          .css('left', spacing * 3)
          .animate({'left': spacing * 2}, 'fast');

        event.preventDefault();
      }).hover(function() {
        $rightRollover.appendTo(this).show();
      }, function() {
        $rightRollover.hide();
      });

    // Center image; enlarge cover.
    $covers.eq(1)
      .css('left', spacing)
      .click(function(event) {
        $waitThrobber.appendTo(this).show();
        var price = $(this).find('.price').text();
        var startPos = $(this).offset();
        startPos.width = $(this).width();
        startPos.height = $(this).height();
        var endPos = {};
        endPos.width = startPos.width * 3;
        endPos.height = startPos.height * 3;
        endPos.top = 100;
        endPos.left = ($('body').width() - endPos.width) / 2;

        $enlargedCover.attr('src', $(this).attr('href'))
          .css(startPos)
          .show();
        var performAnimation = function() {
          $waitThrobber.hide();
          $enlargedCover.animate(endPos, 'normal',
              function() {
            $enlargedCover.one('click', function() {
              $closeButton.unbind('click').hide();
              $priceBadge.hide();
              $enlargedCover.fadeOut();
            });
            $closeButton
              .css({
                'left': endPos.left,
                'top' : endPos.top
              })
              .show()
              .click(function() {
                $enlargedCover.click();
              });
            $priceBadge
              .css({
                'right': endPos.left,
                'top' : endPos.top
              })
              .text(price)
              .show();
          });
        };
        if ($enlargedCover[0].complete) {
          performAnimation();
        }
        else {
          $enlargedCover.bind('load', performAnimation);
        }

        event.preventDefault();
      })
      .hover(function() {
        $enlargeRollover.appendTo(this).show();
      }, function() {
        $enlargeRollover.hide();
      });
  };

  setUpCovers();
});
