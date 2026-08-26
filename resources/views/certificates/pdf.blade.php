<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            text-align: center;
            padding: 60px;
        }
        .border-frame {
            border: 6px solid #D62828;
            border-radius: 12px;
            padding: 60px 40px;
        }
        .label {
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #999;
            font-size: 14px;
        }
        .student-name {
            font-size: 32px;
            font-weight: bold;
            margin: 20px 0;
        }
        .cert-type {
            font-size: 22px;
            color: #D62828;
            margin-bottom: 20px;
        }
        .meta {
            color: #666;
            font-size: 13px;
            margin-top: 30px;
        }
    </style>
</head>
<body>

    <div class="border-frame">

        <div class="label">Certificate of Completion</div>

        <div class="student-name">{{ $userCertification->user->name }}</div>

        <p>has been awarded</p>

        <div class="cert-type">{{ $userCertification->certificationType->name }}</div>

        @if($userCertification->certificate_num)
        <p>Certificate No. {{ $userCertification->certificate_num }}</p>
        @endif

        @if(!is_null($userCertification->score))
        <p>Score: {{ rtrim(rtrim(number_format($userCertification->score, 2), '0'), '.') }}</p>
        @endif

        <div class="meta">
            Issued {{ $userCertification->created_at->format('d F Y') }}
            @if($userCertification->expired_at)
            · Valid until {{ $userCertification->expired_at->format('d F Y') }}
            @endif
        </div>

    </div>

</body>
</html>