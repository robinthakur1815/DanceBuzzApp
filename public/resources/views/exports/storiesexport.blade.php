<table>
    <thead>
    <tr>
        @foreach ($headers as $header)
        <th>{{$header}}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>

          @foreach ($bodies as $body)
          <tr>
            @foreach ($body as $b)
             <td>{{$b}}</td>
            @endforeach
          </tr>
        @endforeach
</table>
