<p> Hey {{$name}}, Welcome to The Shop Company. </p> <br>
<p> You are successfully registered!</p>
<p> Your profile information:</p>
<ul>
    <li>E-mail: {{$email}}</li>

    @if(!empty($github))
        <li>Github: {{$github}}</li>
    @endif

    @if(!empty($trello))
        <li>Trello: {{$trello}}</li>
    @endif

    @if(!empty($slack))
        <li>Slack : {{$slack}}</li>
    @endif

</ul>
<p> We would like to invite you to our slack team!</p>
    @if(empty($slack))
        <p>Please, go visit <a href="https://slack.com/signin">https://slack.com/signin</a></p>
        @endif
<ul>
    <li> Slack name: The Shop</li>
    <li> Slack domain: the-shop-company</li>
</ul>


