(function() {
    var $form = $('#frm-blueprintEditor-form');
    if (!$form.length) {
        return;
    }

    var Renderer = {};
    Renderer.saveHook = function(def, $input, map) {
        var serialize = function() {
            for (var k in map) {
                def[k] = map[k].val();
            }
            $input.val(JSON.stringify(def));
        };
        for (var k in map) {
            map[k].on('change', serialize);
        }
        serialize();
    };
	Renderer.label = function(type) {
		switch (type) {
			case 'integer':
				return 'celé číslo';
			case 'list':
				return 'náhodné slovo';
			case 'plural':
				return 'plurál';
		}
	};
    Renderer.render = function(def, $input) {
	    var $varType = $('<span/>').addClass('variable-type').text(this.label(def.type));
        var $group = $('<div/>').addClass('variable-options');
        $input.hide();
	    $input.before($varType);
        $input.parent().append($group);

        var method = 'render' + def.type.charAt(0).toUpperCase() + def.type.slice(1);
	    Renderer[method](def, $group, $input);
    };
    Renderer.renderInteger = function(def, $group, $input) {
        var $min = $('<input type="number" class="form-control">').val(def.min);
        var $max = $('<input type="number" class="form-control">').val(def.max);
        $group.append($min).append($max);

        Renderer.saveHook(def, $input, {
            'min': $min,
            'max': $max
        });
    };
    Renderer.renderPlural = function(def, $group, $input) {
        var inputs = {};
        var keys = ['count', 'one', 'few', 'many'];
        for (var name in keys) {
            var key = keys[name];
            inputs[key] = $('<input class="form-control">').val(def[key]);
            $group.append(inputs[key]);
        }
        Renderer.saveHook(def, $input, inputs);
    };
    Renderer.renderList = function(def, $group, $input) {
	    var list = def.list; // prevent weird race condition
	    list.push("");
	    var inputs = {};
        for (var i in list) {
            inputs[i] = $('<input class="form-control">').val(list[i]);
            $group.append(inputs[i]);

            var serialize = function() {
                def.list = [];
                for (var i in inputs) {
	                var val = inputs[i].val().trim();
	                if (val) {
		                def.list.push(val);
	                }
                }
                $input.val(JSON.stringify(def));
            };
            inputs[i].on('change', serialize);
            serialize();
        }
        $input.val(JSON.stringify(def));
    };

    $form.find('[data-definition]').each(function(i, input) {
        var def = JSON.parse($(input).val());
        Renderer.render(def, $(input));
    });



	var highlight = function($input) {
		var $underlay = $input.siblings('.underlay');
		if (!$underlay.length) {
			$underlay = $('<div/>').addClass('underlay');
			$input.before($underlay);
		}
		var text = $input.val();
		text = text.replace(/<(\w+)\b/g, '<span class="syntax-$1">&lt;$1');
		text = text.replace(/(<\/.*?>)/g, '$1</span>');
		text = text.replace(/<(?!\/?span)/g, '&lt;');
		$underlay.html(text);
	};

	$form.find('[name=question], [name=answer], [name^=hints]').on('change keyup', function() {
		highlight($(this));
	}).each(function() {
		highlight($(this)); // on load
	});


	$form.find('[data-add-hint]').on('click', function() {
		var id = $(this).parent().children('input').length;
		var $input = $('<input type="text" name="hints[' + id + '][hint]" class="form-control">');
		$input.on('change keyup', function() {
			highlight($(this));
		});

		var $hint = $('<div/>').addClass('hint');
		$hint.append($input);
		$(this).before($hint);
		return false;
	});

    var $container = $form.find('[data-definitions]');
    $form.find('[data-add-type]').on('click', function() {
        var type = $(this).data('add-type');
        var id = $container.find('[data-definition]').length;
        var $name = $('<input name="vars[' + id + '][name]" class="form-control">');
        var $input = $('<input name="vars[' + id + '][definition]" data-definition class="form-control">');
	    var $var = $('<div/>').addClass('variable');
	    $var.append($name).append($input);
        $container.append($var);

        var defaults = {type: type};
        if (type === 'integer') {
            defaults.min = 0;
            defaults.max = 100;

        } else if (type === 'list') {
            defaults.list = [];

        } else if (type === 'plural') {
            defaults = {
                type: type,
                count: '<eval></eval>',
                one: '',
                few: '',
                many: ''
            };
        }
        Renderer.render(defaults, $input);
    });

    var $undo;
    var $pointer;
    var $undoLink;
    $container.find('[data-var-remove]').click(function(e) {
        if ($undoLink) {
            $undoLink.remove();
        }

        var $link = $(e.target);
        var $group = $container.find('[data-var-id="' + $link.data('var-remove') + '"]');
        $pointer = $('<div></div>');
        $group.after($pointer);
        $undoLink = $('<a href="#"></a>').text($link.data('label-undo'))
            .on('click', function() {
                $pointer.after($undo);
                $pointer.remove();
                $undoLink.remove();
            });

        $group.after($undoLink);
        $group.detach();
        $undo = $group;
    });
})();