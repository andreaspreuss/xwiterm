<?php

// As condições
set_time_limit(0);
error_reporting(E_ALL);

// As crianças
include("process.class.php");
include("terminal.class.php");

// Apenas para receber comandos.
if(isset($_POST['stdin'])){
    Terminal::postCommand($_POST['stdin']);
    exit;
}

// A autenticação
if (!isset($_SERVER['PHP_AUTH_USER']) ||
    !Terminal::autenticate($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW'])) {
    header('WWW-Authenticate: Basic realm="xwiterm (use your linux login)"');
    header('HTTP/1.0 401 Unauthorized');
    echo "Authentication failure :)";
    exit;
}




// UTF8 manolo! o/
header("Content-type: text/html; charset=utf8");


?>
<html>
    <script  type="text/javascript" src="jquery.js"></script>
    <script type="text/javascript">
        var bash_history = [];
        var bash_stop = 0;
        function manda(valor){
            $.post("index.php", {"stdin":valor}, function(data){return true;});
		$("[name='stdin']").val("").focus();
                bash_history.push(valor);
                bash_stop = bash_history.length;
        }

        // muito cara de aula de C da faculdade kkkk
        function recebe(valor){
            // Esses replaces acho que vão pro PHP.
//            var c = String.fromCharCode(27);
//            var regexp = new RegExp(c + '\\[\\d{2};\\d{2}m(.+)' + c + '\\[0m$', 'mg');
//            var regexp = new RegExp(c + '.+\\d{1}m(.+)' + c + '\\[0m', 'mg');
//            valor = valor.replace(regexp, "<b>$1</b>");
            $("label").before( valor );
            $(".stdin").parent().css({
                //"border":"1px solid white",//debug
                "left":$("pre > span:last").width() + "px"
            });
            window.scrollTo(0,document.body.scrollHeight);
        }
        function bottom(valor){
            $(".bottom").html(valor);
        }
        function ctrlc(){
            $.post("stdin.php", {"stdin":String.fromCharCode(3)}, function(data){return true;});
		$("[name='stdin']").val("").focus();
        }
    </script>
    <style>
    body {
        background-color:#000;
        color:#FFF;
    }
    .stdin {
        background-color:#000000;
        border:0 none;
        color:#FFFFFF;
        font-family:inherit;
        font-size:12px;
        line-height:inherit;
        margin:0;
        outline-style:none;
        outline-width:0;
        padding:0;
        width:100%;
    }
    p{ margin:0;}
    label {
        position:absolute;
        right:0; left:200;
    }
    .bottom { /* future... */
        border:1px solid white;
    }
    </style>
    <body>
        <pre><label><input type="text" name="stdin" class="stdin" autocomplete="off"/></label></pre>
        
    </body>
    <script>
        // É meio óbvio porque tem essa tag script aqui e não
        // lá em cima dentro de um $(document).ready(), né?
        // Pra quem não entendeu, em breve uma explicação.
        $(".stdin").keydown(function(e){
                code = e.keyCode ? e.keyCode : e.which;

                if(code.toString() == 13) manda(this.value);
                if(code.toString() == 38) {
                    if(bash_stop <= 0) bash_stop = 1;
                    this.value = bash_history[bash_stop-1];
                    bash_stop--;
                }
                if(code.toString() == 40) {
                    bash_stop++;
                    if(bash_stop >= bash_history.length){
                        this.value = '';
                        bash_stop = bash_history.length;
                    } else {
                        this.value = bash_history[bash_stop];
                    }

                }
                if(e.ctrlKey && code.toString() == 67) {
                    manda(String.fromCharCode(3));
                    e.preventDefault();
                }
                if(e.ctrlKey && code.toString() == 68) {
                    manda(String.fromCharCode(4));
                    e.preventDefault();
                }
                if(e.ctrlKey && code.toString() == 90) {
                    manda(String.fromCharCode(26));
                    e.preventDefault();
                }
        });
        $(".stdin").val("").focus();
        // isso é um COCO, mas fica igualzinho ao terminal
        $("body").click(function(){
            $(".stdin").val("").focus();
        })
    </script>
</html>
<?php Terminal::run($_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']); ?>