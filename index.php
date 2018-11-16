<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Отчет по сотрудникам<</title>
    </head>
    <body>

        <div class="container">
            <h1>Отчет по сотрудникам</h1>

            <form method="post" action="/public/controllers/timesheet.php" class="mb-5">
                <div class="form-group">
                    <label for="begin-date" class="col-2 col-form-label">От</label>
                    <div class="col-10">
                        <input class="form-control" type="date" name="beginDate" value="2017-02-11" id="begin-date"/>
                    </div>
                </div>

                <div class="form-group">
                    <label for="end-date" class="col-2 col-form-label">До</label>
                    <div class="col-10">
                        <input class="form-control" type="date" name="endDate" value="2017-02-12" id="end-date"/>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-circle-o-notch fa-spin"></i>
                    Сформировать отчет
                </button>
                <div class="loader"></div>

            </form>

            <div class="result-container"></div>

            <div class="alert alert-danger error-container"></div>
        </div>

        <script src="/public/assert/js/bundle.js"></script>
    </body>
</html>
