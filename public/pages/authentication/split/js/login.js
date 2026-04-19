var CanvasBG = (function () {
  var width;
  var height;
  var target = { x: 0, y: 0 };
  var animateHeader = true;
  var canvas;
  var ctx;
  var points = [];

  function init(options) {
    canvas = document.getElementById('demo-canvas');
    if (!canvas) {
      return;
    }

    width = window.innerWidth;
    height = window.innerHeight;
    target = options && options.Loc ? options.Loc : { x: width / 2, y: height / 2 };

    canvas.width = width;
    canvas.height = height;
    ctx = canvas.getContext('2d');

    points = [];
    for (var x = 0; x < width; x = x + width / 20) {
      for (var y = 0; y < height; y = y + height / 20) {
        var px = x + Math.random() * (width / 20);
        var py = y + Math.random() * (height / 20);
        var point = { x: px, originX: px, y: py, originY: py };
        points.push(point);
      }
    }

    for (var i = 0; i < points.length; i++) {
      var closest = [];
      var p1 = points[i];

      for (var j = 0; j < points.length; j++) {
        var p2 = points[j];
        if (p1 === p2) {
          continue;
        }

        var placed = false;
        for (var k = 0; k < 5; k++) {
          if (!closest[k]) {
            closest[k] = p2;
            placed = true;
            break;
          }
        }

        if (!placed) {
          for (var m = 0; m < 5; m++) {
            if (getDistance(p1, p2) < getDistance(p1, closest[m])) {
              closest[m] = p2;
              break;
            }
          }
        }
      }

      p1.closest = closest;
      p1.circle = new Circle(p1, 2 + Math.random() * 2, 'rgba(156,217,249,0.3)');
    }

    addListeners();
    for (var n = 0; n < points.length; n++) {
      shiftPoint(points[n]);
    }
    animate();
  }

  function addListeners() {
    if (!('ontouchstart' in window)) {
      window.addEventListener('mousemove', mouseMove);
    }
    window.addEventListener('scroll', scrollCheck);
    window.addEventListener('resize', resize);
  }

  function mouseMove(e) {
    var posx = e.pageX;
    var posy = e.pageY;
    target.x = posx;
    target.y = posy;
  }

  function scrollCheck() {
    animateHeader = document.body.scrollTop <= height || document.documentElement.scrollTop <= height;
  }

  function resize() {
    width = window.innerWidth;
    height = window.innerHeight;
    canvas.width = width;
    canvas.height = height;
  }

  function animate() {
    if (animateHeader) {
      ctx.clearRect(0, 0, width, height);
      for (var i = 0; i < points.length; i++) {
        if (Math.abs(getDistance(target, points[i])) < 4000) {
          points[i].active = 0.3;
          points[i].circle.active = 0.6;
        } else if (Math.abs(getDistance(target, points[i])) < 20000) {
          points[i].active = 0.15;
          points[i].circle.active = 0.4;
        } else if (Math.abs(getDistance(target, points[i])) < 40000) {
          points[i].active = 0.08;
          points[i].circle.active = 0.2;
        } else {
          points[i].active = 0;
          points[i].circle.active = 0;
        }

        drawLines(points[i]);
        points[i].circle.draw();
      }
    }
    requestAnimFrame(animate);
  }

  function shiftPoint(point) {
    TweenLite.to(point, 1 + 1 * Math.random(), {
      x: point.originX - 50 + Math.random() * 100,
      y: point.originY - 50 + Math.random() * 100,
      ease: Circ.easeInOut,
      onComplete: function () {
        shiftPoint(point);
      }
    });
  }

  function drawLines(point) {
    if (!point.active) {
      return;
    }

    for (var i = 0; i < point.closest.length; i++) {
      ctx.beginPath();
      ctx.moveTo(point.x, point.y);
      ctx.lineTo(point.closest[i].x, point.closest[i].y);
      ctx.strokeStyle = 'rgba(156,217,249,' + point.active + ')';
      ctx.stroke();
    }
  }

  function Circle(pos, rad, color) {
    this.pos = pos;
    this.radius = rad;
    this.color = color;
    this.active = 0;

    this.draw = function () {
      if (!this.active) {
        return;
      }
      ctx.beginPath();
      ctx.arc(this.pos.x, this.pos.y, this.radius, 0, 2 * Math.PI, false);
      ctx.fillStyle = 'rgba(156,217,249,' + this.active + ')';
      ctx.fill();
    };
  }

  function getDistance(p1, p2) {
    return Math.pow(p1.x - p2.x, 2) + Math.pow(p1.y - p2.y, 2);
  }

  return {
    init: init
  };
})();
