<div class="modal fade" id="export_options" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{$url ?? ''}}" method="post">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Export Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="row mb-2">
                        <div class="col">
                            <h6>
                                Message Time Range
                            </h6>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <label for="start_time" class="form-label">Start Time</label>
                            <input id="start_time" name="start_time" type="datetime-local" class="form-control">
                        </div>
                        <div class="col-sm-6">
                            <label for="end_time" class="form-label">End Time</label>
                            <input id="end_time" name="end_time" type="datetime-local" class="form-control">
                        </div>
                    </div>
                    {{ $slot }}
                </div>
                <div class="modal-footer">
                    <button type="button" data-bs-dismiss="modal" class="btn btn-outline-danger btn-sm">
                        Close
                    </button>
                    <button type="submit" class="btn btn-success btn-sm">
                        Export
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
