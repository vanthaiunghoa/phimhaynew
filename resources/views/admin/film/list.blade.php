@extends('admin.master')
@section('header')
<div class="col-lg-12">
    <h1 class="page-header bg-primary">Danh Sách Phim
    </h1>
</div>
@endsection
@section('content')
<div class="col-lg-12 admin-detail">
    <table class="table table-bordered table-hover table-striped">
        <caption>Danh sách</caption>
        <thead>
            <tr>
                <th>ID</th>
                <th>Tên Tiếng Việt</th>
                <th>Tên Tiếng Anh</th>
                <th>Đường Dẫn</th>
                <th>Trạng Thái</th>
                <th>Source Trailer</th>
                <th>Ngày Update</th>
                <th>Ngày Tạo</th>
                <th>Xem Chi Tiết</th>
                <th>Chỉnh Sửa</th>
                <th>Xóa</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($films as $film)
                <tr>
                    <td>{!! $film->id !!}</td>
                    <td>{!! $film->filmList->film_name_vn !!}</td>
                    <td>{!! $film->filmList->film_name_en !!}</td>
                    <td>{!! $film->filmList->film_dir_name !!}</td>
                    <td>{!! $film->filmList->film_status !!}</td>
                    <td>{!! $film->filmTrailer->film_src_full !!}</td>
                    <td>{!! $film->filmList->updated_at !!}</td>
                    <td>{!! $film->filmList->created_at !!}</td>
                    <td><a href="{!! route('admin.film.getShow', $film->id) !!}">Xem</a></td>
                    <td><a href="{!! route('admin.film.getEdit', $film->id) !!}">Sửa</a></td>
                    <td>Xoa</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <?php echo $films->render(); ?>
</div>
@endsection