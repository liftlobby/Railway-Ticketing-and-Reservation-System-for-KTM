<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="stylep.css">
    <title>Profile</title>
</head>

<body>
    <?php include 'Head_and_Foot\header.php'; ?>
    <div class="container">
        <h4 class="font-weight-bold py-3 mb-4">User Profile Settings</h4>
        <div class="card">
            <div class="row">
                <div class="col-md-3">
                    <div class="list-group">
                        <a class="list-group-item active" href="#account-general">General</a>
                        <a class="list-group-item" href="#account-change-password">Change password</a>
                        <a class="list-group-item" href="#account-info">Info</a>
                    </div>
                </div>
                <div class="col-md-9">
                    <div id="account-general" class="tab-pane active">
                        <div class="card-body media">
                            <img src="https://bootdey.com/img/Content/avatar/avatar1.png" alt="Profile Picture"
                                class="ui-w-80">
                            <div class="media-body">
                                <label class="btn btn-outline-primary">
                                    Upload new photo
                                    <input type="file" class="account-settings-fileinput" style="display: none;">
                                </label>
                                <button type="button" class="btn btn-default">Reset</button>
                                <div class="text-light small">Allowed JPG, GIF or PNG. Max size of 800K</div>
                            </div>
                        </div>
                        <hr>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="nmaxwell">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" value="Nelle Maxwell">
                            </div>
                            <div class="form-group">
                                <label class="form-label">E-mail</label>
                                <input type="text" class="form-control" value="nmaxwell@mail.com">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Company</label>
                                <input type="text" class="form-control" value="Company Ltd.">
                            </div>
                        </div>
                    </div>

                    <div id="account-change-password" class="tab-pane">
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Current password</label>
                                <input type="password" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">New password</label>
                                <input type="password" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Repeat new password</label>
                                <input type="password" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div id="account-info" class="tab-pane">
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Bio</label>
                                <textarea class="form-control" rows="5">Tell us something about you...</textarea>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Birthday</label>
                                <input type="text" class="form-control" value="Dec 15, 2024">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Country</label>
                                <select class="form-control">
                                    <option>Singapore</option>
                                    <option selected>Malaysia</option>
                                    <option>Indonesia</option>
                                    <option>Vietnam</option>
                                    <option>Philippines</option>
                                </select>
                            </div>
                        </div>
                        <hr>
                        <div class="card-body">
                            <h6>Contacts</h6>
                            <div class="form-group">
                                <label class="form-label">Phone</label>
                                <input type="text" class="form-control" value="+60123456789">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-right">
            <button type="button" class="btn btn-primary">Save changes</button>
            <button type="button" class="btn btn-default">Cancel</button>
        </div>
    </div>

    <?php include 'Head_and_Foot\footer.php'; ?>
</body>

</html>
