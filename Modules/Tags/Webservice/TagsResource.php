<?php

namespace Modules\Tags\Webservice;

use Bazalt\Data\Validator;
use Tonic\Response;
use Modules\Tags\Model\Tag;

/**
 * TagsResource
 *
 * @uri /tags
 */
class TagsResource extends \Bazalt\Rest\Resource
{
    /**
     * @method GET
     * @json
     */
    public function find()
    {
        $q = isset($_GET['q']) ? $_GET['q'] : '';
        $items = Tag::find($q);
        $ret = [];
        foreach ($items as $item) {
            $ret [] = [
                'id' => $item->body,
                'text' => $item->body
            ];
        }
        return new Response(Response::OK, array(
            'tags' => $ret
        ));
    }

    /**
     * @method POST
     * @json
     */
    public function setMassTags()
    {
        $data = Validator::create((array)$this->request->data);

        if (!$data->validate()) {
            return new Response(400, $data->errors());
        }

        if (count($data['tags']) > 0 && count($data['usersIds']) > 0) {
            foreach ($data['tags'] as $tag) {
                foreach ($data['usersIds'] as $userId) {
                    Tag::addTag((int)$userId, 'user', $tag);
                }
            }
        }
    }
}
