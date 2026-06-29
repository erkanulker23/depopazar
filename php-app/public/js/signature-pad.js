/**
 * Basit dokunmatik imza alanı (canvas).
 */
(function (global) {
    'use strict';

    function SignaturePad(canvas) {
        this.canvas = canvas;
        this.ctx = canvas.getContext('2d');
        this.drawing = false;
        this.hasInk = false;
        this._bind();
        this.resize();
    }

    SignaturePad.prototype._bind = function () {
        var self = this;
        var start = function (e) {
            e.preventDefault();
            self.drawing = true;
            self.hasInk = true;
            var p = self._point(e);
            self.ctx.beginPath();
            self.ctx.moveTo(p.x, p.y);
        };
        var move = function (e) {
            if (!self.drawing) return;
            e.preventDefault();
            var p = self._point(e);
            self.ctx.lineTo(p.x, p.y);
            self.ctx.stroke();
        };
        var end = function (e) {
            if (!self.drawing) return;
            e.preventDefault();
            self.drawing = false;
        };
        this.canvas.addEventListener('mousedown', start);
        this.canvas.addEventListener('mousemove', move);
        this.canvas.addEventListener('mouseup', end);
        this.canvas.addEventListener('mouseleave', end);
        this.canvas.addEventListener('touchstart', start, { passive: false });
        this.canvas.addEventListener('touchmove', move, { passive: false });
        this.canvas.addEventListener('touchend', end, { passive: false });
        this.canvas.addEventListener('touchcancel', end, { passive: false });
    };

    SignaturePad.prototype._point = function (e) {
        var rect = this.canvas.getBoundingClientRect();
        var t = e.touches && e.touches[0] ? e.touches[0] : (e.changedTouches && e.changedTouches[0] ? e.changedTouches[0] : e);
        return {
            x: t.clientX - rect.left,
            y: t.clientY - rect.top,
        };
    };

    SignaturePad.prototype._applyStrokeStyle = function () {
        this.ctx.strokeStyle = '#111827';
        this.ctx.lineWidth = 2.5;
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';
    };

    SignaturePad.prototype.clear = function () {
        var w = this.canvas.width / (window.devicePixelRatio || 1);
        var h = this.canvas.height / (window.devicePixelRatio || 1);
        this.ctx.fillStyle = '#ffffff';
        this.ctx.fillRect(0, 0, w, h);
        this._applyStrokeStyle();
        this.hasInk = false;
    };

    SignaturePad.prototype.isEmpty = function () {
        return !this.hasInk;
    };

    SignaturePad.prototype.toDataURL = function () {
        return this.canvas.toDataURL('image/png');
    };

    SignaturePad.prototype.fromDataURL = function (url, done) {
        var self = this;
        if (!url) {
            self.clear();
            if (done) done();
            return;
        }
        var img = new Image();
        img.onload = function () {
            var w = self.canvas.width / (window.devicePixelRatio || 1);
            var h = self.canvas.height / (window.devicePixelRatio || 1);
            self.ctx.fillStyle = '#ffffff';
            self.ctx.fillRect(0, 0, w, h);
            self.ctx.drawImage(img, 0, 0, w, h);
            self._applyStrokeStyle();
            self.hasInk = true;
            if (done) done();
        };
        img.onerror = function () {
            if (done) done();
        };
        img.src = url;
    };

    SignaturePad.prototype.resize = function () {
        var canvas = this.canvas;
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        var w = canvas.offsetWidth || 300;
        var h = canvas.offsetHeight || 120;
        var data = this.hasInk ? this.toDataURL() : null;
        canvas.width = Math.floor(w * ratio);
        canvas.height = Math.floor(h * ratio);
        this.ctx = canvas.getContext('2d');
        this.ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        if (data) {
            this.fromDataURL(data);
        } else {
            this.clear();
        }
    };

    global.SignaturePad = SignaturePad;
})(typeof window !== 'undefined' ? window : this);
