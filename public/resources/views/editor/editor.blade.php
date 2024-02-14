<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <title>{!! $data !!}</title>
</head>
<body>
<!-- Create the editor container -->
<div id="editor">
  {!! $data !!}
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js">   </script>
<!-- Include the Quill library -->
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<!-- Initialize Quill editor -->
<script>
  
  $( document ).ready(() => {
    $("#editor").bind("DOMSubtreeModified", function() {
      let title = $("div.ql-editor").html()
      document.title = title
    })

    let quill = new Quill('#editor', {
      theme: 'snow'
    })
    $("div.ql-editor").css("min-heigh", "300px");
  })
</script>
</body>
</html>
