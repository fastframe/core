<?php
if (count($errors))
{
?>
<script type="text/javascript">
function showErrors()
{
    errorWindow = window.open('', 'errorConsole', 'resizable=yes,scrollbars=yes,directories=no,location=no,menubar=no,status=no,toolbar=no');
    errorWindow.document.open();
    errorWindow.document.writeln('<html><head><title>errorWindow Console: <?php echo $_SERVER['PHP_SELF']; ?></title>');
    errorWindow.document.writeln('<style>body { font-size: 12px; } span.errorLevel { font-weight: bold; letter-spacing: 2px; text-transform: uppercase; color: green; } div.errorMessage { font-weight: bold; font-family: courier; color: red; } </style>');
    errorWindow.document.writeln('<script>function errorJump(index, offset) { if (element = document.getElementById(\'error\' + (index + offset))) { window.scrollTo(0, element.offsetTop); } return void(0); }</scr' + 'ipt>');
    errorWindow.document.writeln('</head><body>');
    <?php
    foreach ($errors as $index => $error) {
    ?>
    errorWindow.document.writeln('<div id="error<?php echo $index; ?>" style="float: right;">Error Number: <?php echo $index; ?> | <a href="javascript: errorJump(<?php echo $index; ?>, -1);">prev</a> | <a href="javascript: errorJump(<?php echo $index; ?>, 1);">next</a></div><?php echo $error; ?><div style="height: 1px; line-height: 1px; background-color: black; border: 0; margin-bottom: 5px;"><br /></div>');
    <?php
    }
    ?>
    errorWindow.document.writeln('</body></html>');
    errorWindow.document.close();
    return false;
}
</script>
<div style="position: fixed; right: 5px; bottom: 5px;"><img src="./graphics/general/core.png" style="cursor: pointer; cursor: hand;" onclick="return showErrors();" title="Error Reporter" alt="Error Reporter" /></div>
<?php
}
?>
