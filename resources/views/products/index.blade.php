@extends('layouts.app')

@section('content')

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Products</h1>
</div>


<div class="card">
    <div id="filterForm" class="card-header">
        <div class="form-row justify-content-between">
            <div class="col-md-2">
                <input type="text" name="title" id="title" placeholder="Product Title" class="form-control">
            </div>
            <div class="col-md-2">
                <select name="variant" id="variant" class="form-control">
                    <option value="">Select</option>
                    @foreach ($variants as $variant)
                    <option value="{{$variant->id}}">{{$variant->title}}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">Price Range</span>
                    </div>
                    <input type="text" name="price_from" id="price_from" aria-label="First name" placeholder="From" class="form-control">
                    <input type="text" name="price_to" id="price_to" aria-label="Last name" placeholder="To" class="form-control">
                </div>
            </div>
            <div class="col-md-2">
                <input type="date" name="date" id="date" placeholder="Date" class="form-control">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-primary float-right" onclick="filterProduct()"><i class="fa fa-search"></i></button>
            </div>
        </div>
    </div>

    <div class="card-body">
        <div class="table-response">
            <table class="table" id="product_table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Description</th>
                        <!-- <th>Variant</th> -->
                        <th width="50px">Action</th>
                    </tr>
                </thead>

                <!-- <tbody>

                    <tr>
                        <td>1</td>
                        <td>T-Shirt <br> Created at : 25-Aug-2020</td>
                        <td>Quality product in low cost</td>
                        <td>
                            <dl class="row mb-0" style="height: 80px; overflow: hidden" id="variant">

                                <dt class="col-sm-3 pb-0">
                                    SM/ Red/ V-Nick
                                </dt>
                                <dd class="col-sm-9">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4 pb-0">Price : {{ number_format(200,2) }}</dt>
                                        <dd class="col-sm-8 pb-0">InStock : {{ number_format(50,2) }}</dd>
                                    </dl>
                                </dd>
                            </dl>
                            <button onclick="$('#variant').toggleClass('h-auto')" class="btn btn-sm btn-link">Show more</button>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('product.edit', 1) }}" class="btn btn-success">Edit</a>
                            </div>
                        </td>
                    </tr>

                </tbody> -->

            </table>
        </div>

    </div>

    <!-- <div class="card-footer">
        <div class="row justify-content-between">
            <div class="col-md-6">
                <p>Showing 1 to 10 out of 100</p>
            </div>
            <div class="col-md-2">

            </div>
        </div>
    </div> -->
</div>
<script>
    $(document).ready(function() {
        product_list();
    })

    function filterProduct() {
        product_list();
    }

    function product_list() {
        $('#product_table').DataTable({
            destroy: true,
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{url("product")}}',
                method: 'GET',
                data: function(d) {
                    d.title = $("#title").val();
                    d.variant = $("#variant").val();
                    d.price_from = $("#price_from").val();
                    d.price_to = $("#price_to").val();
                    d.date = $("#date").val();
                    d._token = '{!! csrf_token() !!}';
                }
            },
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex'
                },
                {
                    data: 'title',
                    name: 'title'
                },
                {
                    data: 'description',
                    name: 'description'
                },
                // {
                //     data: 'sku',
                //     name: 'sku'
                // },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },
            ]
        });
    }
</script>
@endsection