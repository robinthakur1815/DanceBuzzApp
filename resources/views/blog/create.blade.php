<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <title>Document</title>
</head>
<body>
    <div class = "container">
        <form method="POST" enctype="multipart/form-data" action="/img">
            {{ csrf_field() }}
            <div class="form-group">
                <label for="exampleFormControlInput1">Text</label>
                <input type="text" name= 'name' class="form-control" id="exampleFormControlInput1" placeholder="name@example.com">
            </div>
            <div class = "form-group">
                <label for ="exampleFormControlInput1">Thread</label>
                <input name="img" type="file" />
            </div>
            <br>            
            <button type="submit">Submit</button>
            @if ($errors->any())
            <div class = "notification is-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                      <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
      @endif
        </form>
    </div>
</body>
</html>