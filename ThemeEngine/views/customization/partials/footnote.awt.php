<div class="page-footnote">
    <form method="post"
          action="/dashboard/customization/save"
          class="d-flex flex-wrap align-items-center gap-2">
        @include('customization/partials/form-context.awt.php')
        <input type="hidden" name="operation" value="cache_clear">
        <input type="hidden" name="tab" value="{{ $tab }}">

        <span>Source cache refreshes automatically when JSON files change.</span>
        <button type="submit" class="btn btn-outline-secondary"><i class="fa-solid fa-broom fa-fw" aria-hidden="true"></i> Clear cache</button>
    </form>
</div>
