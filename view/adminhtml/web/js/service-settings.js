require([
    'jquery',
    'mage/backend/form'
], function($) {

    function getField(id)
    {
        var prefix = 'service_';
        id = '#' + prefix + id;
        return $(id);
    }

    function setFieldValue(id, newValue)
    {
        var field = getField(id), value = field.val();
        // if (!value || 0 === value.length) {
            field.val(newValue);
        // }
    }

    function toggleDepends(key)
    {
        var depends = {
            0:  '0010000',//sendmail
            10: '1111111',//smtp
            15: '1100000',//gmail
            20: '1110000',//ses
            30: '1100000' //mandrill
        },depend,
        elements = ['user', 'password', 'email', 'host', 'port', 'secure', 'auth'];

        if ('undefined' == typeof depends[key]) {
            depend = '1111111';
        } else {
            depend = depends[key];
        }
        $(elements).each(function(id, index) {
            var el = getField(index).closest('.field');
            if (depend[id] == 1) {
                el.show();
            } else {
                el.hide();
            }
        });
    }

    function toggleAuth(key)
    {
        var depends = {
            'none':  '00',//none
            'login': '11',//login
            'plain': '11',//plain
            'crammd5': '11',//crammd5
        },depend,
        elements = ['user', 'password'];

        if ('undefined' == typeof depends[key]) {
            depend = '11';
        } else {
            depend = depends[key];
        }
        $(elements).each(function(id, index) {
            var el = getField(index).closest('.field');
            if (depend[id] == 1) {
                el.show();
            } else {
                el.hide();
            }
        });
    }

    function fill(settings) {

        $.each(settings, setFieldValue);
    }

    $('#service_type').change(function(e) {
        var settings, depend, depends;
        toggleDepends(this.value);

        var optionSelected = $('option:selected', this);
        if (optionSelected[0] && optionSelected[0].title) {
            settings = optionSelected[0].title;
            settings = JSON.parse(JSON.parse(settings));
            if(settings.host) {
                fill(settings);
            }
        }

        depends = {
            0:  'none',//sendmail
            10: 'login',//smtp
            15: 'login',//gmail
            20: 'login',//ses
            30: 'login' //mandrill
        }

        if ('undefined' == typeof depends[this.value]) {
            depend = $('#service_auth').val();
        } else {
            depend = depends[this.value];
        }

        toggleAuth(depend);
    });

    $('#service_auth').change(function(e) {
        toggleAuth(this.value);
    });
    toggleAuth($('#service_auth').val());
    toggleDepends($('#service_type').val());
});
