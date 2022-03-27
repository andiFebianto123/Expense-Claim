@extends(backpack_view('blank'))


@section('content')
<div class="row">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-header font-weight-bold bg-primary">
                Waiting Approval
            </div>
            <ul class="list-group list-group-flush">
                @foreach ($dataWaitingApproval as $item)
                    @if ($item['has_access'])
                    <li class="list-group-item list-hover">
                        <a href="{{$item['url']}}" target="_blank" class="d-flex flex-wrap align-items-center text-dark">
                            <div class="mr-2">
                                {{$item['title']}}
                            </div>
                            <div class="ml-auto">
                                <h4 class="mb-0 font-weight-bold">{{$item['count']}}</h4>
                            </div>
                        </a>
                    </li>
                    @else
                    <li class="list-group-item d-flex flex-wrap align-items-center">
                        <div class="mr-2">
                            {{$item['title']}}
                        </div>
                        <div class="ml-auto">
                            <h4 class="mb-0 font-weight-bold">{{$item['count']}}</h4>
                        </div>
                    </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-header font-weight-bold bg-primary">
                Need Approval
            </div>
            <ul class="list-group list-group-flush">
                @foreach ($dataNeedApproval as $item)
                    @if ($item['has_access'])
                    <li class="list-group-item list-hover">
                        <a href="{{$item['url']}}" target="_blank" class="d-flex flex-wrap align-items-center text-dark">
                            <div class="mr-2">
                                {{$item['title']}}
                            </div>
                            <div class="ml-auto">
                                <h4 class="mb-0 font-weight-bold">{{$item['count']}}</h4>
                            </div>
                        </a>
                    </li>
                    @else
                    <li class="list-group-item d-flex flex-wrap align-items-center">
                        <div class="mr-2">
                            {{$item['title']}}
                        </div>
                        <div class="ml-auto">
                            <h4 class="mb-0 font-weight-bold">{{$item['count']}}</h4>
                        </div>
                    </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-header font-weight-bold bg-primary">
                Request
            </div>
            <ul class="list-group list-group-flush">
                @foreach ($dataRequest as $item)
                    @if ($item['has_access'])
                    <li class="list-group-item list-hover">
                        <a href="{{$item['url']}}" target="_blank" class="d-flex flex-wrap align-items-center text-dark">
                            <div class="mr-2">
                                {{$item['title']}}
                            </div>
                            <div class="ml-auto">
                                <h4 class="mb-0 font-weight-bold">{{$item['count']}}</h4>
                            </div>
                        </a>
                    </li>
                    @else
                    <li class="list-group-item d-flex flex-wrap align-items-center">
                        <div class="mr-2">
                            {{$item['title']}}
                        </div>
                        <div class="ml-auto">
                            <h4 class="mb-0 font-weight-bold">{{$item['count']}}</h4>
                        </div>
                    </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
@push('after_styles')
<style>
    .list-hover{
        padding: 0;
        cursor: pointer;
    }
    .list-hover a{
        padding: .75rem 1.25rem;
    }
    .list-hover a:hover{
        text-decoration: none;
    }
    .list-hover:hover{
        background-color: rgba(0, 106, 237, .15);
    }
</style>
@endpush
@push('after_scripts')
    <script>

    </script>
@endpush