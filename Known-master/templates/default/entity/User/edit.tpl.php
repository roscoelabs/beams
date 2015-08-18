<div class="container col-md-11 col-md-offset-1">
    <div class="row beforecontent">
        <h1>Edit your profile</h1>

        <p>
            Your profile is how other users see you across the site. It's up to you how much or how little
            information you choose to provide.
        </p>
    </div>

    <form class="form-horizontal" role="form" action="<?= $vars['user']->getDisplayURL() ?>" method="post"
          enctype="multipart/form-data">
        <div class="row">
            <!-- left column -->
            <div class="col-md-3">
                <div class="text-center">

                    <div id="photo-preview"><img src="<?= \Idno\Core\site()->session()->currentUser()->getIcon() ?>"
                                                 class="avatar img-circle"
                                                 alt="avatar" style="width: 100px"></div>

                        <span class="btn btn-primary btn-file">
                            <i class="fa fa-camera"></i> 
                        <span id="photo-filename">Select a user picture</span>
                            <input type="file" name="avatar" id="photo"
                                   class="form-control"
                                   accept="image/*;capture=camera"
                                   onchange="photoPreview(this)"/>

                        </span>
                </div>
            </div>

            <!-- edit form column -->
            <div class="col-md-8 personal-info">

                <div class="form-group">
                    <label class="control-label" for="name">Your name</label>
                    <input class="form-control" type="text" id="name" name="name"
                           value="<?= htmlspecialchars($vars['user']->getTitle()) ?>">
                </div>

                <div class="form-group">
                    <label class="control-label" for="tagline">Short description</label>
                    <input class="form-control" type="text" id="tagline" name="profile[tagline]"
                           value="<?= htmlspecialchars($vars['user']->getShortDescription()) ?>">
                </div>

                <div class="form-group">
                    <label class="control-label" for="body">About you</label><br>

                    <textarea name="profile[description]" id="body"
                              class="form-control bodyInput"><?= htmlspecialchars($vars['user']->getDescription()) ?></textarea>


                </div>

                <div class="form-group">
                    <p>
                        <label for="website">
                            Your websites</label><br>
                        <small>Other places on the web where people can find you.</small>
                    </p>
                    <div id="websitelist">
                        <?php

                            if (!empty($vars['user']->profile['url'])) {
                                if (!is_array($vars['user']->profile['url'])) {
                                    $urls = array($vars['user']->profile['url']);
                                } else {
                                    $urls = $vars['user']->profile['url'];
                                }
                                foreach ($urls as $url) {
                                    if (!empty($url)) {
                                        ?>
                                        <div class="row">
                                            <div class="col-md-10"><input type="text" name="profile[url][]" id="website"
                                                                          value="<?= htmlspecialchars($this->fixURL($url)) ?>"
                                                                          placeholder="http://" class="form-control"/>
                                            </div>
                                            <div class="col-md-2" style="margin-top: 0.75em">
                                                <small><a href="#"
                                                          onclick="$(this).parent().parent().parent().remove(); return false;">Remove</a>
                                                </small>
                                            </div>
                                        </div>
                                    <?php
                                    }
                                }
                            }

                        ?>
                        <div class="row">
                            <div class="col-md-10">
                                <input type="text" name="profile[url][]" id="title" value="" placeholder="http://"
                                       class="form-control"/></div>
                            <div class="col-md-2" style="margin-top: 0.75em">
                                <small >
                                    <a href="#" onclick="$(this).parent().parent().parent().remove(); return false;">Remove</a>
                                </small>
                            </div>
                        </div>
                    </div>
                    <p>
                        <small><a href="#"
                                  onclick="$('#websitelist').append($('#form-website-template').html()); return false;">+
                                Add more</a></small>
                    </p>
                </div>

                <div class="form-group">
                    <p>
                        <?= \Idno\Core\site()->actions()->signForm('/profile/' . $vars['user']->getHandle()) ?>
                        <input type="button" class="btn btn-cancel" value="Cancel" onclick="hideContentCreateForm();"/>
                        <input type="submit" class="btn btn-primary" value="Save Changes"/>
                    </p>


                </div>
            </div>
        </div>


    </form>
    <div id="form-website-template" style="display:none">
        <div class="row">
            <div class="col-md-10">
                <input type="text" name="profile[url][]" id="title" value="" placeholder="http://"
                       class="form-control"/></div>
            <div class="col-md-2" style="margin-top: 0.75em">
                <small>
                    <a href="#" onclick="$(this).parent().parent().parent().remove(); return false;">Remove</a>
                </small>
            </div>
        </div>
    </div>
</div>


<script>
    //if (typeof photoPreview !== function) {
    function photoPreview(input) {

        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $('#photo-preview').html('<img src="" id="photopreview" style="width: 100px">');
                $('#photo-filename').html('Choose different user picture');
                $('#photopreview').attr('src', e.target.result);
                $('#photopreview').show();
            }

            reader.readAsDataURL(input.files[0]);
        }
    }
    //}
</script>