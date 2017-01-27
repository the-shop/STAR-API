<div style="padding: 20px">
    <p>
        Employee : <strong>{{$name}}</strong>,<br/>
        Here are salary stats for period of <strong>{{$fromDate}}</strong> to <strong>{{$toDate}}</strong>:
    </p>

    <p>
        Base gross salary: <strong>{{$realGrossPayout}} HRK</strong>
    </p>

    <p>
        Gross bonus: <strong>{{$grossBonusPayout}} HRK</strong>
    </p>

    <p>
        Gross total: <strong>{{$realGrossPayout + $grossBonusPayout}} HRK</strong>
    </p>
</div>