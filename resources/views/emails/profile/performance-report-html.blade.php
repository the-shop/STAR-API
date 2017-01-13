<div style="padding: 20px">
    <p>
        Hey <strong>{{$name}}</strong>,<br/>
        Here are your stats for period of <strong>{{$fromDate}}</strong> to <strong>{{$toDate}}</strong>:
    </p>

    <p>
        Estimated hours: <strong>{{$estimatedHours}}</strong>
    </p>

    <p>
        Delivered hours: <strong>{{$estimatedHours}}</strong>
    </p>

    <p>
        Total payout on external projects: <strong>{{$totalPayoutExternal}}</strong>
    </p>

    <p>
        Real payout on external projects: <strong>{{$realPayoutExternal}}</strong>
    </p>

    <p>
        Total payout on internal projects: <strong>{{$totalPayoutInternal}}</strong>
    </p>

    <p>
        Real payout on internal projects: <strong>{{$realPayoutInternal}}</strong>
    </p>

    <p>
        Total payout combined: <strong>{{$totalPayoutCombined}}</strong>
    </p>

    <p>
        Real payout combined: <strong>{{$realPayoutCombined}}</strong>
    </p>

    @if($xpDiff !== 0)
        <p>
            Oh and your XP changed by <strong>{{$xpDiff}}</strong> and you now have total of
            <strong>{{$xpTotal}}</strong> XP.
        </p>
    @endif
</div>